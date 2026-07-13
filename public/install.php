<?php

declare(strict_types=1);

// Installer selalu menampilkan error agar tidak muncul halaman kosong (blank)
// saat terjadi masalah. Halaman & file ini terhapus otomatis setelah instalasi.
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

define('ROOTPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

require ROOTPATH . 'install/InstallerWeb.php';

use Install\InstallerWeb;

if (! function_exists('install_esc')) {
    function install_esc(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (InstallerWeb::isInstalled()) {
    header('Location: ./', true, 302);
    exit;
}

$step    = max(1, min(3, (int) ($_GET['step'] ?? 1)));
$errors  = [];

if ($step === 3 && ! isset($_SESSION['install_db']) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    header('Location: install.php?step=1', true, 302);
    exit;
}

$displayStep = $step;

if ($step === 2 && isset($_SESSION['install_db'])) {
    $displayStep = 3;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_token'] ?? '';

    if (! is_string($token) || ! hash_equals($_SESSION['install_token'] ?? '', $token)) {
        $errors[] = 'Sesi tidak valid. Muat ulang halaman dan coba lagi.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'database') {
            $db = [
                'hostname' => trim((string) ($_POST['hostname'] ?? 'localhost')),
                'database' => trim((string) ($_POST['database'] ?? '')),
                'username' => trim((string) ($_POST['username'] ?? '')),
                'password' => (string) ($_POST['password'] ?? ''),
                'port'     => trim((string) ($_POST['port'] ?? '3306')),
            ];

            if ($db['database'] === '' || $db['username'] === '') {
                $errors[] = 'Nama database dan username wajib diisi.';
            } else {
                $dbError = InstallerWeb::testDatabase($db);

                if ($dbError !== null) {
                    $errors[] = $dbError;
                } else {
                    $_SESSION['install_db'] = $db;
                    header('Location: install.php?step=2', true, 302);
                    exit;
                }
            }
        }

        if ($action === 'install') {
            $db = $_SESSION['install_db'] ?? null;

            if (! is_array($db)) {
                header('Location: install.php?step=1', true, 302);
                exit;
            }

            $data = [
                'baseURL'        => trim((string) ($_POST['baseURL'] ?? '')),
                'hostname'       => $db['hostname'],
                'database'       => $db['database'],
                'username'       => $db['username'],
                'password'       => $db['password'],
                'port'           => $db['port'],
                'admin_name'     => trim((string) ($_POST['admin_name'] ?? 'Admin')),
                'admin_email'    => trim((string) ($_POST['admin_email'] ?? '')),
                'admin_password' => (string) ($_POST['admin_password'] ?? ''),
                'force_https'    => isset($_POST['force_https']),
            ];

            if ($data['baseURL'] === '') {
                $data['baseURL'] = InstallerWeb::detectBaseUrl();
            }

            $installError = InstallerWeb::install($data);

            if ($installError !== null) {
                $errors[] = $installError;
                $step     = 2;
            } else {
                unset($_SESSION['install_db'], $_SESSION['install_token']);
                header('Location: ./login', true, 302);
                exit;
            }
        }
    }
}

if (! isset($_SESSION['install_token'])) {
    $_SESSION['install_token'] = bin2hex(random_bytes(32));
}

$requirements = InstallerWeb::requirements();
$requirementsOk = InstallerWeb::requirementsMet();
$dbSession = $_SESSION['install_db'] ?? [
    'hostname' => 'localhost',
    'database' => '',
    'username' => '',
    'password' => '',
    'port'     => '3306',
];
$detectedUrl = InstallerWeb::detectBaseUrl();
$https = str_starts_with($detectedUrl, 'https://');

require ROOTPATH . 'install/views/wizard.php';
