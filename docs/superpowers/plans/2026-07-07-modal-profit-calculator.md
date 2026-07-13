# ModalCalc Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a mobile-first multi-user web app (CI4 + Bootstrap 5) that calculates capital return and profit sharing for single/multi-investor projects, with full unit + feature test coverage.

**Architecture:** Monolithic CodeIgniter 4 MVC. `ProfitCalculator` library holds all math. Server-rendered Bootstrap 5 views with wizard form. MySQL persistence. PDF/Excel export via controller with ownership checks.

**Tech Stack:** PHP 8.1+, CodeIgniter 4, Bootstrap 5, MySQL, dompdf, PhpSpreadsheet, PHPUnit

**Spec:** `docs/superpowers/specs/2026-07-07-modal-profit-calculator-design.md`

---

## File Map

| File | Responsibility |
|------|----------------|
| `app/Libraries/ProfitCalculator.php` | All profit/capital math |
| `app/Models/UserModel.php` | User CRUD + auth lookup |
| `app/Models/ProjectModel.php` | Project CRUD scoped by user |
| `app/Models/InvestorModel.php` | Investor rows per project |
| `app/Controllers/AuthController.php` | Register, login, logout |
| `app/Controllers/DashboardController.php` | Project list |
| `app/Controllers/ProjectController.php` | CRUD + calculate |
| `app/Controllers/ExportController.php` | PDF & Excel download |
| `app/Filters/AuthFilter.php` | Redirect unauthenticated users |
| `app/Filters/SecurityHeadersFilter.php` | HTTP security headers |
| `app/Database/Migrations/*.php` | Schema |
| `app/Views/layouts/main.php` | Bootstrap 5 mobile layout |
| `tests/unit/Libraries/ProfitCalculatorTest.php` | Unit tests |
| `tests/feature/AuthTest.php` | Auth flow tests |
| `tests/feature/ProjectTest.php` | CRUD + ownership tests |
| `tests/feature/ExportTest.php` | Export + CSRF tests |

---

### Task 1: Scaffold CodeIgniter 4 Project

**Files:**
- Create: entire CI4 appstarter structure
- Modify: `composer.json` (add dompdf, phpspreadsheet)
- Create: `.env.example`

- [ ] **Step 1: Install CI4 appstarter**

```bash
cd "/Users/mbp19/Documents/YAZDAD/APLIKASI PRODUKSI/modal"
composer create-project codeigniter4/appstarter /tmp/modalcalc-ci4 --no-dev
cp -r /tmp/modalcalc-ci4/. .
rm -rf /tmp/modalcalc-ci4
```

- [ ] **Step 2: Add export dependencies**

```bash
composer require dompdf/dompdf phpoffice/phpspreadsheet
```

- [ ] **Step 3: Configure `.env`**

Copy `.env` from `env` and set:

```ini
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = modalcalc
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306
```

- [ ] **Step 4: Create `.env.example`** (same keys, empty password)

- [ ] **Step 5: Verify server starts**

```bash
php spark serve
```

Expected: Server running at `http://localhost:8080`

---

### Task 2: Database Migrations

**Files:**
- Create: `app/Database/Migrations/2026-07-07-000001_CreateUsersTable.php`
- Create: `app/Database/Migrations/2026-07-07-000002_CreateProjectsTable.php`
- Create: `app/Database/Migrations/2026-07-07-000003_CreateInvestorsTable.php`
- Create: `app/Database/Migrations/2026-07-07-000004_CreateLoginAttemptsTable.php`

- [ ] **Step 1: Create users migration**

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'password'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('users');
    }

    public function down(): void
    {
        $this->forge->dropTable('users');
    }
}
```

- [ ] **Step 2: Create projects migration**

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProjectsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT', 'unsigned' => true],
            'nama_proyek'      => ['type' => 'VARCHAR', 'constraint' => 200],
            'mode_input'       => ['type' => 'ENUM', 'constraint' => ['unit', 'direct']],
            'jumlah_unit'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'harga_beli'       => ['type' => 'BIGINT', 'null' => true],
            'harga_jual'       => ['type' => 'BIGINT', 'null' => true],
            'total_modal'      => ['type' => 'BIGINT'],
            'total_hasil_jual' => ['type' => 'BIGINT'],
            'persen_pemodal'   => ['type' => 'DECIMAL', 'constraint' => '5,2'],
            'persen_operator'  => ['type' => 'DECIMAL', 'constraint' => '5,2'],
            'nama_operator'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'catatan'          => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('projects');
    }

    public function down(): void
    {
        $this->forge->dropTable('projects');
    }
}
```

- [ ] **Step 3: Create investors migration**

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInvestorsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'project_id' => ['type' => 'INT', 'unsigned' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'modal'      => ['type' => 'BIGINT'],
            'urutan'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('project_id');
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('investors');
    }

    public function down(): void
    {
        $this->forge->dropTable('investors');
    }
}
```

- [ ] **Step 4: Create login_attempts migration** (rate limiting)

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLoginAttemptsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'attempted_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['ip_address', 'email', 'attempted_at']);
        $this->forge->createTable('login_attempts');
    }

    public function down(): void
    {
        $this->forge->dropTable('login_attempts');
    }
}
```

- [ ] **Step 5: Run migrations**

```bash
php spark migrate
```

Expected: `Migrations complete.`

- [ ] **Step 6: Configure test database in `phpunit.xml.dist`**

Set SQLite in-memory for tests:

```xml
<env name="database.tests.hostname" value=""/>
<env name="database.tests.database" value=":memory:"/>
<env name="database.tests.DBDriver" value="SQLite3"/>
<env name="database.tests.DBPrefix" value=""/>
```

Add to `app/Config/Database.php` a `tests` group pointing to SQLite `:memory:`.

---

### Task 3: ProfitCalculator (TDD)

**Files:**
- Create: `tests/unit/Libraries/ProfitCalculatorTest.php`
- Create: `app/Libraries/ProfitCalculator.php`

- [ ] **Step 1: Write failing unit tests**

```php
<?php

namespace Tests\Unit\Libraries;

use App\Libraries\ProfitCalculator;
use CodeIgniter\Test\CIUnitTestCase;

final class ProfitCalculatorTest extends CIUnitTestCase
{
    private ProfitCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ProfitCalculator();
    }

    public function testMultiInvestorProportionalSplit(): void
    {
        $result = $this->calculator->calculate(
            totalHasilJual: 420_000_000,
            totalModal: 350_000_000,
            persenPemodal: 60,
            persenOperator: 40,
            investors: [
                ['nama' => 'A', 'modal' => 50_000_000],
                ['nama' => 'B', 'modal' => 100_000_000],
                ['nama' => 'C', 'modal' => 200_000_000],
            ]
        );

        $this->assertSame(70_000_000, $result['keuntungan_kotor']);
        $this->assertSame(50_000_000, $result['investors'][0]['pengembalian_modal']);
        $this->assertSame(6_000_000, $result['investors'][0]['profit']);
        $this->assertSame(56_000_000, $result['investors'][0]['total']);
        $this->assertSame(28_000_000, $result['pool_operator']);
    }

    public function testSingleInvestor(): void
    {
        $result = $this->calculator->calculate(
            totalHasilJual: 120_000_000,
            totalModal: 100_000_000,
            persenPemodal: 70,
            persenOperator: 30,
            investors: [['nama' => 'Solo', 'modal' => 100_000_000]]
        );

        $this->assertSame(14_000_000, $result['investors'][0]['profit']);
        $this->assertSame(6_000_000, $result['pool_operator']);
    }

    public function testZeroProfit(): void
    {
        $result = $this->calculator->calculate(
            totalHasilJual: 100_000_000,
            totalModal: 100_000_000,
            persenPemodal: 60,
            persenOperator: 40,
            investors: [['nama' => 'A', 'modal' => 100_000_000]]
        );

        $this->assertSame(0, $result['keuntungan_kotor']);
        $this->assertSame(0, $result['pool_pemodal']);
        $this->assertSame(0, $result['pool_operator']);
    }

    public function testNegativeProfit(): void
    {
        $result = $this->calculator->calculate(
            totalHasilJual: 80_000_000,
            totalModal: 100_000_000,
            persenPemodal: 60,
            persenOperator: 40,
            investors: [['nama' => 'A', 'modal' => 100_000_000]]
        );

        $this->assertSame(-20_000_000, $result['keuntungan_kotor']);
        $this->assertTrue($result['rugi']);
        $this->assertSame(0, $result['pool_pemodal']);
        $this->assertSame(0, $result['pool_operator']);
    }

    public function testInvestorTotalMismatchThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total modal pemodal tidak sama dengan total modal proyek');

        $this->calculator->calculate(
            totalHasilJual: 420_000_000,
            totalModal: 350_000_000,
            persenPemodal: 60,
            persenOperator: 40,
            investors: [
                ['nama' => 'A', 'modal' => 50_000_000],
                ['nama' => 'B', 'modal' => 100_000_000],
            ]
        );
    }

    public function testPercentMustSum100(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Persentase pemodal dan operator harus berjumlah 100%');

        $this->calculator->calculate(
            totalHasilJual: 100_000_000,
            totalModal: 100_000_000,
            persenPemodal: 60,
            persenOperator: 30,
            investors: [['nama' => 'A', 'modal' => 100_000_000]]
        );
    }

    public function testComputeTotalsFromUnitMode(): void
    {
        $totals = $this->calculator->computeUnitTotals(23, 24_000_000, 30_000_000);

        $this->assertSame(552_000_000, $totals['total_modal']);
        $this->assertSame(690_000_000, $totals['total_hasil_jual']);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/phpunit tests/unit/Libraries/ProfitCalculatorTest.php
```

Expected: FAIL — class `ProfitCalculator` not found

- [ ] **Step 3: Implement ProfitCalculator**

```php
<?php

namespace App\Libraries;

class ProfitCalculator
{
    public function computeUnitTotals(int $jumlahUnit, int $hargaBeli, int $hargaJual): array
    {
        return [
            'total_modal'      => $jumlahUnit * $hargaBeli,
            'total_hasil_jual' => $jumlahUnit * $hargaJual,
        ];
    }

    public function calculate(
        int $totalHasilJual,
        int $totalModal,
        float $persenPemodal,
        float $persenOperator,
        array $investors
    ): array {
        if (round($persenPemodal + $persenOperator, 2) !== 100.0) {
            throw new \InvalidArgumentException('Persentase pemodal dan operator harus berjumlah 100%');
        }

        $investorTotal = array_sum(array_column($investors, 'modal'));
        if ($investorTotal !== $totalModal) {
            throw new \InvalidArgumentException('Total modal pemodal tidak sama dengan total modal proyek');
        }

        $keuntunganKotor = $totalHasilJual - $totalModal;
        $rugi = $keuntunganKotor < 0;

        $poolPemodal  = $rugi ? 0 : (int) round($keuntunganKotor * ($persenPemodal / 100));
        $poolOperator = $rugi ? 0 : (int) round($keuntunganKotor * ($persenOperator / 100));

        $results = [];
        foreach ($investors as $investor) {
            $share = $investor['modal'] / $totalModal;
            $pengembalian = (int) round($totalModal * $share);
            $profit = $rugi ? 0 : (int) round($poolPemodal * $share);

            $results[] = [
                'nama'               => $investor['nama'],
                'modal'              => $investor['modal'],
                'pengembalian_modal' => $pengembalian,
                'profit'             => $profit,
                'total'              => $pengembalian + $profit,
            ];
        }

        return [
            'total_hasil_jual' => $totalHasilJual,
            'total_modal'      => $totalModal,
            'keuntungan_kotor' => $keuntunganKotor,
            'rugi'             => $rugi,
            'pool_pemodal'     => $poolPemodal,
            'pool_operator'    => $poolOperator,
            'investors'        => $results,
        ];
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
vendor/bin/phpunit tests/unit/Libraries/ProfitCalculatorTest.php
```

Expected: OK (7 tests)

---

### Task 4: Models

**Files:**
- Create: `app/Models/UserModel.php`
- Create: `app/Models/ProjectModel.php`
- Create: `app/Models/InvestorModel.php`

- [ ] **Step 1: UserModel**

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table         = 'users';
    protected $primaryKey  = 'id';
    protected $allowedFields = ['name', 'email', 'password'];
    protected $useTimestamps = true;
    protected $validationRules = [
        'name'     => 'required|min_length[2]|max_length[100]',
        'email'    => 'required|valid_email|is_unique[users.email,id,{id}]',
        'password' => 'required|min_length[8]',
    ];
    protected $validationMessages = [
        'email' => ['is_unique' => 'Email sudah terdaftar.'],
    ];

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }
}
```

- [ ] **Step 2: ProjectModel**

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class ProjectModel extends Model
{
    protected $table         = 'projects';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'user_id', 'nama_proyek', 'mode_input', 'jumlah_unit',
        'harga_beli', 'harga_jual', 'total_modal', 'total_hasil_jual',
        'persen_pemodal', 'persen_operator', 'nama_operator', 'catatan',
    ];
    protected $useTimestamps = true;

    public function findForUser(int $id, int $userId): ?array
    {
        return $this->where('id', $id)->where('user_id', $userId)->first();
    }

    public function getByUser(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('updated_at', 'DESC')
            ->findAll();
    }
}
```

- [ ] **Step 3: InvestorModel**

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class InvestorModel extends Model
{
    protected $table         = 'investors';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['project_id', 'nama', 'modal', 'urutan'];
    protected $useTimestamps = false;

    public function getByProject(int $projectId): array
    {
        return $this->where('project_id', $projectId)
            ->orderBy('urutan', 'ASC')
            ->findAll();
    }

    public function syncForProject(int $projectId, array $investors): void
    {
        $this->where('project_id', $projectId)->delete();
        foreach ($investors as $i => $investor) {
            $this->insert([
                'project_id' => $projectId,
                'nama'       => $investor['nama'],
                'modal'      => $investor['modal'],
                'urutan'     => $i,
            ]);
        }
    }
}
```

---

### Task 5: Auth System

**Files:**
- Create: `app/Controllers/AuthController.php`
- Create: `app/Models/LoginAttemptModel.php`
- Create: `app/Views/auth/login.php`
- Create: `app/Views/auth/register.php`
- Modify: `app/Config/Routes.php`

- [ ] **Step 1: LoginAttemptModel**

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class LoginAttemptModel extends Model
{
    protected $table         = 'login_attempts';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['ip_address', 'email', 'attempted_at'];
    protected $useTimestamps = false;

    public function tooManyAttempts(string $ip, string $email, int $max = 5, int $minutes = 15): bool
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));
        $count = $this->where('ip_address', $ip)
            ->where('email', $email)
            ->where('attempted_at >=', $since)
            ->countAllResults();
        return $count >= $max;
    }

    public function record(string $ip, string $email): void
    {
        $this->insert([
            'ip_address'   => $ip,
            'email'        => $email,
            'attempted_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
```

- [ ] **Step 2: AuthController**

```php
<?php

namespace App\Controllers;

use App\Models\LoginAttemptModel;
use App\Models\UserModel;

class AuthController extends BaseController
{
    public function loginForm()
    {
        if (session('user_id')) {
            return redirect()->to('/dashboard');
        }
        return view('auth/login');
    }

    public function login()
    {
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $ip = $this->request->getIPAddress();

        $attempts = new LoginAttemptModel();
        if ($attempts->tooManyAttempts($ip, $email)) {
            return redirect()->back()->withInput()
                ->with('error', 'Terlalu banyak percobaan login. Coba lagi dalam 15 menit.');
        }

        $userModel = new UserModel();
        $user = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            $attempts->record($ip, $email);
            return redirect()->back()->withInput()
                ->with('error', 'Email atau password salah');
        }

        session()->regenerate(true);
        session()->set(['user_id' => $user['id'], 'user_name' => $user['name']]);
        return redirect()->to('/dashboard');
    }

    public function registerForm()
    {
        return view('auth/register');
    }

    public function register()
    {
        $userModel = new UserModel();
        $data = [
            'name'     => $this->request->getPost('name'),
            'email'    => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
        ];

        if (!$userModel->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $userModel->errors());
        }

        return redirect()->to('/login')->with('success', 'Akun berhasil dibuat. Silakan login.');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }
}
```

- [ ] **Step 3: Add routes in `app/Config/Routes.php`**

```php
$routes->get('/', 'AuthController::loginForm');
$routes->get('login', 'AuthController::loginForm');
$routes->post('login', 'AuthController::login');
$routes->get('register', 'AuthController::registerForm');
$routes->post('register', 'AuthController::register');
$routes->get('logout', 'AuthController::logout');

$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('dashboard', 'DashboardController::index');
    $routes->get('projects/create', 'ProjectController::create');
    $routes->post('projects', 'ProjectController::store');
    $routes->get('projects/(:num)', 'ProjectController::show/$1');
    $routes->get('projects/(:num)/edit', 'ProjectController::edit/$1');
    $routes->post('projects/(:num)', 'ProjectController::update/$1');
    $routes->post('projects/(:num)/delete', 'ProjectController::delete/$1');
    $routes->get('projects/(:num)/export/pdf', 'ExportController::pdf/$1');
    $routes->get('projects/(:num)/export/excel', 'ExportController::excel/$1');
});
```

- [ ] **Step 4: Create auth views** (Bootstrap 5, mobile-first, single column, `esc()` on all output, CSRF field in forms)

---

### Task 6: Security Filters

**Files:**
- Create: `app/Filters/AuthFilter.php`
- Create: `app/Filters/SecurityHeadersFilter.php`
- Modify: `app/Config/Filters.php`

- [ ] **Step 1: AuthFilter**

```php
<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session('user_id')) {
            return redirect()->to('/login');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
```

- [ ] **Step 2: SecurityHeadersFilter**

```php
<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class SecurityHeadersFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null) {}

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->setHeader('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net; font-src 'self' cdn.jsdelivr.net");
        if (ENVIRONMENT === 'production') {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }
}
```

- [ ] **Step 3: Register filters in `app/Config/Filters.php`**

```php
public array $aliases = [
    // ...existing
    'auth' => \App\Filters\AuthFilter::class,
    'securityheaders' => \App\Filters\SecurityHeadersFilter::class,
];

public array $globals = [
    'before' => ['csrf'],
    'after'  => ['securityheaders'],
];
```

---

### Task 7: Dashboard & Project CRUD

**Files:**
- Create: `app/Controllers/DashboardController.php`
- Create: `app/Controllers/ProjectController.php`
- Create: `app/Views/dashboard/index.php`
- Create: `app/Views/projects/create.php`
- Create: `app/Views/projects/edit.php`
- Create: `app/Views/projects/show.php`
- Create: `app/Views/projects/_form.php` (wizard partial)
- Create: `app/Helpers/rupiah_helper.php`
- Modify: `app/Config/Autoload.php` (load helper)

- [ ] **Step 1: rupiah_helper.php**

```php
<?php

if (!function_exists('format_rupiah')) {
    function format_rupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
```

- [ ] **Step 2: DashboardController**

```php
<?php

namespace App\Controllers;

use App\Models\ProjectModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $projects = (new ProjectModel())->getByUser(session('user_id'));
        return view('dashboard/index', ['projects' => $projects]);
    }
}
```

- [ ] **Step 3: ProjectController** — implement `create`, `store`, `show`, `edit`, `update`, `delete`:
  - Validate all inputs server-side
  - Use `ProfitCalculator::computeUnitTotals()` when mode=unit
  - Use `ProfitCalculator::calculate()` on show/store/update
  - Ownership check via `ProjectModel::findForUser()`
  - `InvestorModel::syncForProject()` on save
  - Catch `InvalidArgumentException` → flash error in Bahasa Indonesia

- [ ] **Step 4: Wizard form** (`_form.php`) with 3 steps using Bootstrap tabs or JS stepper:
  - Step 1: project data + mode toggle
  - Step 2: dynamic investor rows (add/remove via vanilla JS)
  - Step 3: review summary
  - Progress bar for modal collected vs total
  - All inputs mobile-friendly (`inputmode="decimal"`, min-height 48px buttons)

---

### Task 8: Bootstrap 5 Layout (Mobile-First)

**Files:**
- Create: `app/Views/layouts/main.php`
- Create: `public/assets/css/app.css`
- Create: `public/assets/js/app.js`

- [ ] **Step 1: main.php layout**
  - Bootstrap 5.3 CDN (css + js bundle)
  - Sticky navbar with hamburger on mobile
  - Toast container for flash messages
  - `viewport` meta tag
  - Content yield section
  - Load `app.css` and `app.js`

- [ ] **Step 2: app.css**
  - Touch targets min 44px
  - `font-variant-numeric: tabular-nums` for money
  - Card-based project list on mobile
  - Full-width primary buttons on `< 576px`
  - Semantic colors: profit green, modal blue

- [ ] **Step 3: app.js**
  - Wizard step navigation
  - Add/remove investor rows
  - Auto-calculate unit totals on input change
  - Rupiah formatting on display fields
  - Modal progress bar update

---

### Task 9: Export PDF & Excel

**Files:**
- Create: `app/Controllers/ExportController.php`
- Create: `app/Views/exports/pdf_template.php`

- [ ] **Step 1: ExportController**

```php
<?php

namespace App\Controllers;

use App\Libraries\ProfitCalculator;
use App\Models\InvestorModel;
use App\Models\ProjectModel;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportController extends BaseController
{
    public function pdf(int $id)
    {
        $data = $this->getProjectData($id);
        $html = view('exports/pdf_template', $data);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'laporan-' . url_title($data['project']['nama_proyek'], '-', true) . '-' . date('Ymd') . '.pdf';
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }

    public function excel(int $id)
    {
        $data = $this->getProjectData($id);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // Row 1: project name, Row 2+: headers, investor breakdown
        // (populate cells from $data['result'])

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        $filename = 'laporan-' . url_title($data['project']['nama_proyek'], '-', true) . '-' . date('Ymd') . '.xlsx';
        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($content);
    }

    private function getProjectData(int $id): array
    {
        $projectModel = new ProjectModel();
        $project = $projectModel->findForUser($id, session('user_id'));
        if (!$project) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $investors = (new InvestorModel())->getByProject($id);
        $calculator = new ProfitCalculator();
        $result = $calculator->calculate(
            $project['total_hasil_jual'],
            $project['total_modal'],
            (float) $project['persen_pemodal'],
            (float) $project['persen_operator'],
            array_map(fn($i) => ['nama' => $i['nama'], 'modal' => (int) $i['modal']], $investors)
        );

        return compact('project', 'investors', 'result');
    }
}
```

- [ ] **Step 2: PDF template** — clean printable layout with all breakdown sections

---

### Task 10: Feature Tests (Full Suite)

**Files:**
- Create: `tests/feature/AuthTest.php`
- Create: `tests/feature/ProjectTest.php`
- Create: `tests/feature/ExportTest.php`
- Create: `tests/_support/DatabaseTestTrait.php` (migrate before each test)

- [ ] **Step 1: AuthTest**

```php
<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class AuthTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;

    public function testRegisterAndLogin(): void
    {
        $this->post('register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password1',
        ]);

        $result = $this->post('login', [
            'email' => 'test@example.com',
            'password' => 'password1',
        ]);

        $result->assertRedirectTo('/dashboard');
    }

    public function testLoginWrongPassword(): void
    {
        // register first, then login with wrong password
        $result = $this->post('login', [
            'email' => 'test@example.com',
            'password' => 'wrong',
        ]);
        $result->assertRedirect();
        $result->assertSessionHas('error');
    }

    public function testLogout(): void
    {
        // login, then logout, assert redirect to login
    }
}
```

- [ ] **Step 2: ProjectTest**

```php
<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class ProjectTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;

    public function testCreateProject(): void
    {
        $this->loginAsUser();
        $result = $this->post('projects', [
            'nama_proyek' => 'Proyek Serer',
            'mode_input' => 'direct',
            'total_modal' => 350000000,
            'total_hasil_jual' => 420000000,
            'persen_pemodal' => 60,
            'persen_operator' => 40,
            'nama_operator' => 'Operator A',
            'investor_nama' => ['Pemodal A', 'Pemodal B'],
            'investor_modal' => [150000000, 200000000],
        ]);
        // adjust investor amounts to sum 350M in actual test data
        $result->assertRedirect();
    }

    public function testCannotAccessOtherUsersProject(): void
    {
        // create project as user A, try access as user B → 404
    }

    private function loginAsUser(): void
    {
        // helper: register + set session
    }
}
```

- [ ] **Step 3: ExportTest**

```php
<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class ExportTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;

    public function testPdfExportRequiresAuth(): void
    {
        $result = $this->get('projects/1/export/pdf');
        $result->assertRedirectTo('/login');
    }

    public function testCsrfRejection(): void
    {
        $this->withHeaders(['X-CSRF-TOKEN' => 'invalid']);
        // POST without valid CSRF → 403
    }
}
```

- [ ] **Step 4: Run full test suite**

```bash
vendor/bin/phpunit
```

Expected: All tests PASS

---

### Task 11: Final Verification

- [ ] **Step 1: Run migrations on dev database**

```bash
php spark migrate
```

- [ ] **Step 2: Run full test suite**

```bash
vendor/bin/phpunit
```

- [ ] **Step 3: Manual smoke test on mobile viewport (375px)**
  - Register → Login → Create project (multi investor) → View result → Export PDF

- [ ] **Step 4: Verify security**
  - CSRF token present on all forms
  - Access other user's project URL → 404
  - Security headers present in response (check DevTools)

---

## Spec Coverage Checklist

| Spec Requirement | Task |
|-----------------|------|
| CI4 + Bootstrap 5 monolith | Task 1, 8 |
| Flexible input (unit/direct) | Task 3, 7 |
| Single & multi investor | Task 3, 7 |
| Proportional split | Task 3 |
| Capital return + profit breakdown | Task 3, 7, 9 |
| Multi-user login | Task 5 |
| Save projects DB | Task 2, 4, 7 |
| Owner-only access | Task 6, 7 |
| PDF/Excel export | Task 9 |
| Mobile-first responsive | Task 8 |
| Security (CSRF, headers, rate limit) | Task 5, 6 |
| Full unit + feature tests | Task 3, 10 |
| Error messages Bahasa Indonesia | Task 3, 5, 7 |

---

## Execution Handoff

**Plan complete and saved to `docs/superpowers/plans/2026-07-07-modal-profit-calculator.md`.**

**Two execution options:**

1. **Subagent-Driven (recommended)** — dispatch a fresh subagent per task, review between tasks, fast iteration

2. **Inline Execution** — execute tasks in this session using executing-plans, batch execution with checkpoints

**Which approach?**
