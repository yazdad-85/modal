<?php

declare(strict_types=1);

namespace Install;

use App\Models\UserModel;
use Config\Paths;
use mysqli;
use Throwable;

final class InstallerWeb
{
    public const LOCK_FILE = 'install.lock';

    public static function isInstalled(): bool
    {
        return is_file(self::lockPath());
    }

    public static function lockPath(): string
    {
        return self::rootPath() . 'writable' . DIRECTORY_SEPARATOR . self::LOCK_FILE;
    }

    public static function rootPath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR;
    }

    public static function publicPath(): string
    {
        return self::rootPath() . 'public' . DIRECTORY_SEPARATOR;
    }

    public static function scriptPath(): string
    {
        return self::publicPath() . 'install.php';
    }

    /**
     * @return list<array{label: string, ok: bool, hint: string}>
     */
    public static function requirements(): array
    {
        $writable = self::rootPath() . 'writable';
        $checks   = [
            [
                'label' => 'PHP 8.2 atau lebih baru',
                'ok'    => version_compare(PHP_VERSION, '8.2.0', '>='),
                'hint'  => 'Versi saat ini: ' . PHP_VERSION,
            ],
            [
                'label' => 'Ekstensi mysqli',
                'ok'    => extension_loaded('mysqli'),
                'hint'  => 'Diperlukan untuk koneksi MySQL.',
            ],
            [
                'label' => 'Ekstensi mbstring',
                'ok'    => extension_loaded('mbstring'),
                'hint'  => 'Diperlukan oleh CodeIgniter.',
            ],
            [
                'label' => 'Ekstensi json',
                'ok'    => extension_loaded('json'),
                'hint'  => 'Diperlukan untuk konfigurasi.',
            ],
            [
                'label' => 'Ekstensi openssl',
                'ok'    => extension_loaded('openssl'),
                'hint'  => 'Diperlukan untuk enkripsi sesi.',
            ],
            [
                'label' => 'Folder writable dapat ditulis',
                'ok'    => is_dir($writable) && is_writable($writable),
                'hint'  => 'Set permission folder writable ke 755 atau 775.',
            ],
            [
                'label' => 'Root proyek dapat menulis .env',
                'ok'    => is_writable(self::rootPath()) || (
                    file_exists(self::rootPath() . '.env')
                    && is_writable(self::rootPath() . '.env')
                ),
                'hint'  => 'Installer akan membuat file .env di folder utama.',
            ],
        ];

        return $checks;
    }

    public static function requirementsMet(): bool
    {
        foreach (self::requirements() as $check) {
            if (! $check['ok']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array{hostname: string, database: string, username: string, password: string, port: string} $config
     */
    public static function testDatabase(array $config): ?string
    {
        $port = (int) ($config['port'] ?: 3306);

        try {
            $mysqli = @new mysqli(
                $config['hostname'],
                $config['username'],
                $config['password'],
                '',
                $port,
            );
        } catch (Throwable $e) {
            return 'Koneksi gagal: ' . $e->getMessage();
        }

        if ($mysqli->connect_errno) {
            return 'Koneksi gagal: ' . $mysqli->connect_error;
        }

        $database = $config['database'];

        if ($mysqli->select_db($database)) {
            $mysqli->close();

            return null;
        }

        $escaped = $mysqli->real_escape_string($database);
        $mysqli->query("CREATE DATABASE IF NOT EXISTS `{$escaped}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

        if ($mysqli->select_db($database)) {
            $mysqli->close();

            return null;
        }

        $error = $mysqli->error;
        $mysqli->close();

        return 'Tidak dapat mengakses database "' . $database . '". Buat database kosong di cPanel/phpMyAdmin terlebih dahulu. (' . $error . ')';
    }

    public static function detectBaseUrl(): string
    {
        $https = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        $scheme = $https ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '/install.php';
        $base   = rtrim(str_replace('\\', '/', dirname($script)), '/');

        if ($base === '/' || $base === '.') {
            $base = '';
        }

        return $scheme . '://' . $host . $base . '/';
    }

    /**
     * @param array{
     *     baseURL: string,
     *     hostname: string,
     *     database: string,
     *     username: string,
     *     password: string,
     *     port: string,
     *     admin_name: string,
     *     admin_email: string,
     *     admin_password: string,
     *     force_https: bool
     * } $data
     */
    public static function install(array $data): ?string
    {
        if (self::isInstalled()) {
            return 'Aplikasi sudah terinstal.';
        }

        if (! self::requirementsMet()) {
            return 'Persyaratan server belum terpenuhi.';
        }

        $dbError = self::testDatabase([
            'hostname' => $data['hostname'],
            'database' => $data['database'],
            'username' => $data['username'],
            'password' => $data['password'],
            'port'     => $data['port'],
        ]);

        if ($dbError !== null) {
            return $dbError;
        }

        $baseURL = rtrim(trim($data['baseURL']), '/') . '/';

        if (! filter_var($baseURL, FILTER_VALIDATE_URL)) {
            return 'URL situs tidak valid.';
        }

        if (strlen($data['admin_password']) < 8) {
            return 'Password admin minimal 8 karakter.';
        }

        if (! filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
            return 'Email admin tidak valid.';
        }

        $encryptionKey = 'hex2bin:' . bin2hex(random_bytes(32));

        $env = self::buildEnvFile([
            'baseURL'       => $baseURL,
            'hostname'      => $data['hostname'],
            'database'      => $data['database'],
            'username'      => $data['username'],
            'password'      => $data['password'],
            'port'          => $data['port'] ?: '3306',
            'encryptionKey' => $encryptionKey,
            'forceHttps'    => $data['force_https'],
        ]);

        if (! self::writeEnvFile($env)) {
            return 'Gagal menulis file .env. Periksa permission folder.';
        }

        try {
            self::bootstrapCi4();
            service('migrations')->latest();

            $userModel = new UserModel();

            if ($userModel->countAllResults() === 0) {
                if (! $userModel->insert([
                    'name'     => $data['admin_name'],
                    'email'    => $data['admin_email'],
                    'password' => $data['admin_password'],
                ])) {
                    return 'Gagal membuat akun admin: ' . implode(' ', $userModel->errors());
                }
            }

            file_put_contents(self::lockPath(), date('c'));
            self::removeInstallerFiles();
        } catch (Throwable $e) {
            return 'Instalasi gagal: ' . $e->getMessage()
                . ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']';
        }

        return null;
    }

    /**
     * @param array<string, string|bool> $values
     */
    private static function buildEnvFile(array $values): string
    {
        $forceHttps = $values['forceHttps'] ? 'true' : 'false';

        $lines = [
            '#--------------------------------------------------------------------',
            '# ModalCalc — dibuat otomatis oleh installer',
            '#--------------------------------------------------------------------',
            '',
            'CI_ENVIRONMENT = production',
            '',
            'app.baseURL = ' . self::envQuote((string) $values['baseURL']),
            "app.indexPage = ''",
            'app.forceGlobalSecureRequests = ' . $forceHttps,
            "app.appTimezone = 'Asia/Jakarta'",
            '',
            'database.default.hostname = ' . self::envQuote((string) $values['hostname']),
            'database.default.database = ' . self::envQuote((string) $values['database']),
            'database.default.username = ' . self::envQuote((string) $values['username']),
            'database.default.password = ' . self::envQuote((string) $values['password']),
            'database.default.DBDriver = MySQLi',
            'database.default.DBPrefix =',
            'database.default.port = ' . self::envQuote((string) $values['port']),
            '',
            'encryption.key = ' . self::envQuote((string) $values['encryptionKey']),
            '',
        ];

        return implode(PHP_EOL, $lines);
    }

    private static function envQuote(string $value): string
    {
        if ($value === '') {
            return "''";
        }

        if (preg_match('/^[A-Za-z0-9_:@.\/-]+$/', $value)) {
            return $value;
        }

        return "'" . str_replace("'", "\\'", $value) . "'";
    }

    private static function writeEnvFile(string $content): bool
    {
        $path = self::rootPath() . '.env';

        return file_put_contents($path, $content) !== false;
    }

    private static function bootstrapCi4(): void
    {
        if (defined('CI_BOOTED')) {
            return;
        }
        define('CI_BOOTED', true);

        if (! defined('FCPATH')) {
            define('FCPATH', self::publicPath());
        }
        chdir(FCPATH);

        require FCPATH . '../app/Config/Paths.php';
        $paths = new Paths();

        // Boot framework secara manual (setara Boot::bootConsole) TANPA membuat
        // CLIRequest & memuat routes, karena installer berjalan lewat HTTP dan
        // hanya butuh menjalankan migrasi + membuat user admin.

        if (! defined('APPPATH')) {
            define('APPPATH', realpath(rtrim($paths->appDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
        }
        if (! defined('ROOTPATH')) {
            define('ROOTPATH', realpath(APPPATH . '../') . DIRECTORY_SEPARATOR);
        }
        if (! defined('SYSTEMPATH')) {
            define('SYSTEMPATH', realpath(rtrim($paths->systemDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
        }
        if (! defined('WRITEPATH')) {
            define('WRITEPATH', realpath(rtrim($paths->writableDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
        }
        if (! defined('TESTPATH')) {
            define('TESTPATH', realpath(rtrim($paths->testsDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
        }

        require_once APPPATH . 'Config/Constants.php';

        // bootConsole tidak mendefinisikan ENVIRONMENT; installer selalu production.
        if (! defined('ENVIRONMENT')) {
            define('ENVIRONMENT', 'production');
        }

        if (defined('COMPOSER_PATH') && is_file(COMPOSER_PATH)) {
            require_once COMPOSER_PATH;
        }

        require_once SYSTEMPATH . 'Config/DotEnv.php';
        (new \CodeIgniter\Config\DotEnv(ROOTPATH))->load();

        if (is_file(APPPATH . 'Config/Boot/' . ENVIRONMENT . '.php')) {
            require_once APPPATH . 'Config/Boot/' . ENVIRONMENT . '.php';
        }

        if (is_file(APPPATH . 'Common.php')) {
            require_once APPPATH . 'Common.php';
        }
        require_once SYSTEMPATH . 'Common.php';

        require_once SYSTEMPATH . 'Config/AutoloadConfig.php';
        require_once APPPATH . 'Config/Autoload.php';
        require_once SYSTEMPATH . 'Modules/Modules.php';
        require_once APPPATH . 'Config/Modules.php';
        require_once SYSTEMPATH . 'Autoloader/Autoloader.php';
        require_once SYSTEMPATH . 'Config/BaseService.php';
        require_once SYSTEMPATH . 'Config/Services.php';
        require_once APPPATH . 'Config/Services.php';

        \Config\Services::autoloader()
            ->initialize(new \Config\Autoload(), new \Config\Modules())
            ->register();

        service('autoloader')->loadHelpers();
    }

    private static function removeInstallerFiles(): void
    {
        @unlink(self::scriptPath());

        $installDir = self::rootPath() . 'install';

        if (! is_dir($installDir)) {
            return;
        }

        self::removeDirectory($installDir);
    }

    private static function removeDirectory(string $dir): void
    {
        $items = scandir($dir);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                self::removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
