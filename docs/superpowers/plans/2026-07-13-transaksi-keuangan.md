# Transaksi Keuangan Pemodal Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mencatat setor modal, pengembalian modal, dan pengembalian profit per pemodal (bertahap), menampilkan progres di detail proyek, mengunci edit setelah ada transaksi, dan menandai proyek selesai otomatis saat semua kewajiban pemodal lunas.

**Architecture:** Satu tabel `transactions` + `TransactionService` yang menghitung target dari `investors` + `ProfitCalculator`, memvalidasi sisa, dan menyinkronkan `projects.status`. UI dan aksi HTTP hanya di halaman detail proyek. Tombol “Tandai Selesai” diganti indikator pelunasan.

**Tech Stack:** CodeIgniter 4, MySQL/SQLite tests, PHPUnit, Bootstrap 5.

**Spec:** `docs/superpowers/specs/2026-07-13-transaksi-keuangan-design.md`

---

## File map

| File | Responsibility |
|------|----------------|
| `app/Database/Migrations/2026-07-13-000007_CreateTransactionsTable.php` | Skema `transactions` |
| `app/Models/TransactionModel.php` | CRUD + agregasi sum |
| `app/Libraries/TransactionService.php` | Target, progres, validasi, status payload |
| `app/Controllers/ProjectController.php` | show progres; store/delete transaksi; kunci edit; hapus complete |
| `app/Config/Routes.php` | Routes transaksi; hapus complete |
| `app/Views/projects/show.php` | Kartu progres, form, riwayat |
| `public/assets/css/app.css` | Style progres |
| `tests/unit/Libraries/TransactionServiceTest.php` | Unit |
| `tests/feature/TransactionTest.php` | Feature |
| `tests/feature/ProjectTest.php` | Sesuaikan tes complete lama |

Matching target: baris `investors` DB (`id`, urutan ASC) sejajar index dengan `result['investors']` dari `ProfitCalculator`. Sebelum memanggil kalkulator, map investor ke `{nama, modal}` saja.

---

### Task 1: Migration `transactions`

**Files:**
- Create: `app/Database/Migrations/2026-07-13-000007_CreateTransactionsTable.php`

- [ ] **Step 1: Buat migration**

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransactionsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'project_id'  => ['type' => 'INT', 'unsigned' => true],
            'investor_id' => ['type' => 'INT', 'unsigned' => true],
            'jenis'       => ['type' => 'VARCHAR', 'constraint' => 32],
            'jumlah'      => ['type' => 'BIGINT'],
            'tanggal'     => ['type' => 'DATE'],
            'catatan'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'  => ['type' => 'INT', 'unsigned' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('project_id');
        $this->forge->addKey('investor_id');
        $this->forge->addKey(['project_id', 'jenis']);
        $this->forge->addKey('tanggal');
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('investor_id', 'investors', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('transactions');
    }

    public function down(): void
    {
        $this->forge->dropTable('transactions');
    }
}
```

- [ ] **Step 2: Jalankan migrasi**

Run: `php spark migrate`  
Expected: migrasi `2026-07-13-000007` sukses.

- [ ] **Step 3: Commit** (lewati jika repo belum punya commit awal — tanya user dulu)

```bash
git add app/Database/Migrations/2026-07-13-000007_CreateTransactionsTable.php
git commit -m "$(cat <<'EOF'
feat: add transactions table migration

EOF
)"
```

---

### Task 2: `TransactionModel`

**Files:**
- Create: `app/Models/TransactionModel.php`

- [ ] **Step 1: Buat model**

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionModel extends Model
{
    public const JENIS_SETOR               = 'setor_modal';
    public const JENIS_PENGEMBALIAN_MODAL  = 'pengembalian_modal';
    public const JENIS_PENGEMBALIAN_PROFIT = 'pengembalian_profit';

    public const JENIS_LIST = [
        self::JENIS_SETOR,
        self::JENIS_PENGEMBALIAN_MODAL,
        self::JENIS_PENGEMBALIAN_PROFIT,
    ];

    protected $table         = 'transactions';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'project_id', 'investor_id', 'jenis', 'jumlah',
        'tanggal', 'catatan', 'created_by',
    ];
    protected $useTimestamps = true;

    public function getByProject(int $projectId): array
    {
        return $this->where('project_id', $projectId)
            ->orderBy('tanggal', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    public function countByProject(int $projectId): int
    {
        return $this->where('project_id', $projectId)->countAllResults();
    }

    /**
     * @return array<int, array<string, int>> investor_id => [jenis => sum]
     */
    public function sumsGroupedByInvestor(int $projectId): array
    {
        $rows = $this->select('investor_id, jenis, SUM(jumlah) AS total')
            ->where('project_id', $projectId)
            ->groupBy('investor_id, jenis')
            ->findAll();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['investor_id']][$row['jenis']] = (int) $row['total'];
        }

        return $out;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Models/TransactionModel.php
git commit -m "$(cat <<'EOF'
feat: add TransactionModel

EOF
)"
```

---

### Task 3: `TransactionService` + unit tests (TDD)

**Files:**
- Create: `app/Libraries/TransactionService.php`
- Create: `tests/unit/Libraries/TransactionServiceTest.php`

- [ ] **Step 1: Tulis failing unit tests**

```php
<?php

namespace Tests\Unit\Libraries;

use App\Libraries\ProfitCalculator;
use App\Libraries\TransactionService;
use CodeIgniter\Test\CIUnitTestCase;

final class TransactionServiceTest extends CIUnitTestCase
{
    private TransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TransactionService();
    }

    public function testBuildProgressPartialSetor(): void
    {
        $investors = [
            ['id' => 1, 'nama' => 'A', 'modal' => 100_000_000],
            ['id' => 2, 'nama' => 'B', 'modal' => 50_000_000],
        ];
        $calc = (new ProfitCalculator())->calculate(
            180_000_000,
            150_000_000,
            60,
            40,
            [
                ['nama' => 'A', 'modal' => 100_000_000],
                ['nama' => 'B', 'modal' => 50_000_000],
            ]
        );
        $sums = [1 => ['setor_modal' => 40_000_000]];

        $progress = $this->service->buildProgress($investors, $calc, $sums);

        $this->assertSame(40_000_000, $progress['project']['setor']['sudah']);
        $this->assertSame(150_000_000, $progress['project']['setor']['target']);
        $this->assertSame(110_000_000, $progress['project']['setor']['sisa']);
        $this->assertSame(60_000_000, $progress['investors'][0]['setor']['sisa']);
        $this->assertFalse($progress['is_fully_settled']);
    }

    public function testRemainingRejectsOverpay(): void
    {
        $this->assertSame(10, $this->service->remaining(100, 90));
        $this->assertFalse($this->service->canRecord(100, 90, 20));
        $this->assertTrue($this->service->canRecord(100, 90, 10));
    }

    public function testZeroProfitTargetCountsAsSettled(): void
    {
        $investors = [['id' => 1, 'nama' => 'A', 'modal' => 100_000_000]];
        $calc = (new ProfitCalculator())->calculate(
            100_000_000,
            100_000_000,
            60,
            40,
            [['nama' => 'A', 'modal' => 100_000_000]]
        );
        $sums = [
            1 => [
                'setor_modal' => 100_000_000,
                'pengembalian_modal' => 100_000_000,
            ],
        ];

        $progress = $this->service->buildProgress($investors, $calc, $sums);

        $this->assertTrue($progress['is_fully_settled']);
        $this->assertSame(100, $progress['investors'][0]['profit']['persen']);
    }

    public function testResolveStatusPayload(): void
    {
        $done = $this->service->resolveStatusPayload(true, '2026-07-13 10:00:00');
        $this->assertSame('completed', $done['status']);
        $this->assertSame('2026-07-13 10:00:00', $done['completed_at']);

        $open = $this->service->resolveStatusPayload(false, '2026-07-13 10:00:00');
        $this->assertSame('active', $open['status']);
        $this->assertNull($open['completed_at']);
    }
}
```

- [ ] **Step 2: Jalankan — harus FAIL**

Run: `./vendor/bin/phpunit tests/unit/Libraries/TransactionServiceTest.php -v`  
Expected: FAIL (`TransactionService` tidak ada).

- [ ] **Step 3: Implementasi `TransactionService`**

```php
<?php

namespace App\Libraries;

use App\Models\TransactionModel;
use InvalidArgumentException;

class TransactionService
{
    public function remaining(int $target, int $sudah): int
    {
        return max(0, $target - $sudah);
    }

    public function percent(int $target, int $sudah): int
    {
        if ($target <= 0) {
            return 100;
        }

        return (int) round(($sudah / $target) * 100);
    }

    public function canRecord(int $target, int $sudah, int $jumlah): bool
    {
        return $jumlah > 0 && $jumlah <= $this->remaining($target, $sudah);
    }

    /**
     * @param list<array{id:int|string,nama:string,modal:int|string}> $investors
     * @param array<string,mixed> $calcResult
     * @param array<int, array<string,int>> $sumsByInvestor
     * @return array{project: array<string,array<string,int>>, investors: list<array<string,mixed>>, is_fully_settled: bool}
     */
    public function buildProgress(array $investors, array $calcResult, array $sumsByInvestor): array
    {
        $calcInvestors = $calcResult['investors'] ?? [];
        $rows = [];
        $allSettled = $investors !== [];

        $projSetor = ['target' => 0, 'sudah' => 0];
        $projModal = ['target' => 0, 'sudah' => 0];
        $projProfit = ['target' => 0, 'sudah' => 0];

        foreach ($investors as $i => $inv) {
            $id = (int) $inv['id'];
            $calcRow = $calcInvestors[$i] ?? null;
            if ($calcRow === null) {
                throw new InvalidArgumentException('Hasil kalkulasi tidak selaras dengan daftar pemodal.');
            }

            $sums = $sumsByInvestor[$id] ?? [];
            $setorSudah  = (int) ($sums[TransactionModel::JENIS_SETOR] ?? 0);
            $modalSudah  = (int) ($sums[TransactionModel::JENIS_PENGEMBALIAN_MODAL] ?? 0);
            $profitSudah = (int) ($sums[TransactionModel::JENIS_PENGEMBALIAN_PROFIT] ?? 0);

            $setorTarget  = (int) $inv['modal'];
            $modalTarget  = (int) $calcRow['pengembalian_modal'];
            $profitTarget = (int) $calcRow['profit'];

            $setor  = $this->metric($setorTarget, $setorSudah);
            $modal  = $this->metric($modalTarget, $modalSudah);
            $profit = $this->metric($profitTarget, $profitSudah);

            $settled = $setor['sisa'] === 0 && $modal['sisa'] === 0 && $profit['sisa'] === 0;
            if (! $settled) {
                $allSettled = false;
            }

            $rows[] = [
                'investor_id' => $id,
                'nama'        => $inv['nama'],
                'setor'       => $setor,
                'modal'       => $modal,
                'profit'      => $profit,
                'settled'     => $settled,
            ];

            $projSetor['target'] += $setorTarget;
            $projSetor['sudah']  += $setorSudah;
            $projModal['target'] += $modalTarget;
            $projModal['sudah']  += $modalSudah;
            $projProfit['target'] += $profitTarget;
            $projProfit['sudah']  += $profitSudah;
        }

        return [
            'project' => [
                'setor'  => $this->metric($projSetor['target'], $projSetor['sudah']),
                'modal'  => $this->metric($projModal['target'], $projModal['sudah']),
                'profit' => $this->metric($projProfit['target'], $projProfit['sudah']),
            ],
            'investors'        => $rows,
            'is_fully_settled' => $allSettled,
        ];
    }

    /** @return array{target:int,sudah:int,sisa:int,persen:int} */
    private function metric(int $target, int $sudah): array
    {
        return [
            'target' => $target,
            'sudah'  => $sudah,
            'sisa'   => $this->remaining($target, $sudah),
            'persen' => $this->percent($target, $sudah),
        ];
    }

    /** @param array<string,mixed> $progressInvestorRow */
    public function targetForJenis(array $progressInvestorRow, string $jenis): int
    {
        return match ($jenis) {
            TransactionModel::JENIS_SETOR => (int) $progressInvestorRow['setor']['target'],
            TransactionModel::JENIS_PENGEMBALIAN_MODAL => (int) $progressInvestorRow['modal']['target'],
            TransactionModel::JENIS_PENGEMBALIAN_PROFIT => (int) $progressInvestorRow['profit']['target'],
            default => throw new InvalidArgumentException('Jenis transaksi tidak valid.'),
        };
    }

    /** @param array<string,mixed> $progressInvestorRow */
    public function sudahForJenis(array $progressInvestorRow, string $jenis): int
    {
        return match ($jenis) {
            TransactionModel::JENIS_SETOR => (int) $progressInvestorRow['setor']['sudah'],
            TransactionModel::JENIS_PENGEMBALIAN_MODAL => (int) $progressInvestorRow['modal']['sudah'],
            TransactionModel::JENIS_PENGEMBALIAN_PROFIT => (int) $progressInvestorRow['profit']['sudah'],
            default => throw new InvalidArgumentException('Jenis transaksi tidak valid.'),
        };
    }

    /** @return array{status: string, completed_at: string|null} */
    public function resolveStatusPayload(bool $fullySettled, string $now): array
    {
        if ($fullySettled) {
            return ['status' => 'completed', 'completed_at' => $now];
        }

        return ['status' => 'active', 'completed_at' => null];
    }
}
```

- [ ] **Step 4: PASS**

Run: `./vendor/bin/phpunit tests/unit/Libraries/TransactionServiceTest.php -v`  
Expected: 4 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Libraries/TransactionService.php tests/unit/Libraries/TransactionServiceTest.php
git commit -m "$(cat <<'EOF'
feat: add TransactionService with progress helpers

EOF
)"
```

---

### Task 4: Routes + controller

**Files:**
- Modify: `app/Config/Routes.php`
- Modify: `app/Controllers/ProjectController.php`

- [ ] **Step 1: Routes — hapus complete, tambah transaksi**

```php
$routes->post('projects/(:num)/transactions', 'ProjectController::storeTransaction/$1');
$routes->post('projects/(:num)/transactions/(:num)/delete', 'ProjectController::deleteTransaction/$1/$2');
```

Hapus baris `projects/(:num)/complete`.

- [ ] **Step 2: Inject `TransactionModel` + `TransactionService` di constructor**

- [ ] **Step 3: Ubah `show`** — hitung `$progress`, `$transactions`, `$investorNames`, `$hasTransactions`; pass ke view. Map investor ke kalkulator:

```php
$calcInvestors = array_map(
    static fn (array $i): array => ['nama' => $i['nama'], 'modal' => (int) $i['modal']],
    $investors
);
```

(Sesuaikan `runCalculation` agar menerima format ini, atau map di dalam pemanggilan yang sudah ada.)

- [ ] **Step 4: Blokir `edit`/`update` jika `countByProject > 0`** (pesan: proyek sudah punya transaksi tidak dapat diedit). Tetap blokir juga jika `isCompleted`.

- [ ] **Step 5: Hapus method `complete()`**

- [ ] **Step 6: Tambah `storeTransaction`, `deleteTransaction`, `syncProjectSettlement`**

Logika inti `storeTransaction`:
1. Ownership + load investors/ops + calculate
2. Validasi `jenis` ∈ `JENIS_LIST`, tanggal `Y-m-d`, investor milik proyek
3. `jumlah` dari post (strip non-digit)
4. `buildProgress` → `canRecord(target, sudah, jumlah)` — gagal → flash error
5. `insert` transaksi
6. `syncProjectSettlement`

`syncProjectSettlement`:
- Jika `is_fully_settled` dan belum completed → update completed + `completed_at` sekarang
- Jika tidak settled dan sedang completed → reopen active + `completed_at` null
- Jika sudah completed dan tetap settled → **jangan** timpa `completed_at`

`deleteTransaction`: pastikan `transaction.project_id === $id`, delete, sync.

- [ ] **Step 7: Commit**

```bash
git add app/Config/Routes.php app/Controllers/ProjectController.php
git commit -m "$(cat <<'EOF'
feat: store and delete investor transactions with auto settlement

EOF
)"
```

---

### Task 5: Feature tests

**Files:**
- Create: `tests/feature/TransactionTest.php`
- Modify: `tests/feature/ProjectTest.php`

Pola create project **harus sama** dengan `ProjectTest` (field `investor_nama` / `investor_modal` array, angka int OK).

Profit contoh: modal 100jt, hasil 120jt, 60/40 → pool pemodal 12jt → target profit solo = 12_000_000.

- [ ] **Step 1: Buat tests**

Cover:
1. `testStorePartialSetor` — 1 transaksi, status tetap active
2. `testRejectOverpay` — jumlah > target, count tetap 0
3. `testAutoCompleteWhenFullySettled` — setor + pengembalian modal + profit penuh → completed
4. `testDeleteReopensCompletedProject` — hapus salah satu → active
5. `testEditBlockedAfterTransaction` — GET edit redirect ke show

- [ ] **Step 2: Ubah `ProjectTest`**

- Hapus `testCompleteProject` (atau ganti jadi assert POST `/complete` 404)
- Ubah `testCannotEditCompletedProject`: selesaikan lewat 3 transaksi penuh, lalu assert edit redirect

- [ ] **Step 3: Jalankan**

Run: `./vendor/bin/phpunit tests/feature/TransactionTest.php tests/feature/ProjectTest.php -v`  
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add tests/feature/TransactionTest.php tests/feature/ProjectTest.php
git commit -m "$(cat <<'EOF'
test: cover investor transactions and replace manual complete tests

EOF
)"
```

---

### Task 6: UI show page + CSS

**Files:**
- Modify: `app/Views/projects/show.php`
- Modify: `public/assets/css/app.css`

- [ ] **Step 1: Hapus tombol “Tandai Selesai” + `#completeProjectModal` seluruhnya**

Ganti indikator pelunasan (badge selesai otomatis / “Belum lunas — X/Y pemodal tuntas”).

- [ ] **Step 2: Blok UI transaksi** (setelah KPI kalkulasi):

1. Tiga kartu proyek: setor / kembali modal / kembali profit dari `$progress['project']`
2. Kartu per `$progress['investors']` + tombol buka modal (data-investor-id)
3. Modal form POST `projects/{id}/transactions`: investor select, jenis, jumlah, tanggal (default hari ini), catatan
4. Tabel riwayat `$transactions` + form hapus `transactions/{txId}/delete`

Label jenis:

```php
$jenisLabel = [
    'setor_modal' => 'Setor modal',
    'pengembalian_modal' => 'Pengembalian modal',
    'pengembalian_profit' => 'Pengembalian profit',
];
```

Sembunyikan link Edit jika `$hasTransactions || $isCompleted`.

- [ ] **Step 3: CSS**

```css
.tx-progress-card { border-radius: 0.75rem; }
.tx-progress-bar { height: 6px; background: #e9ecef; border-radius: 3px; overflow: hidden; }
.tx-progress-bar > span { display: block; height: 100%; background: #198754; }
```

- [ ] **Step 4: Smoke manual** — `php spark serve --host 127.0.0.1 --port 8081`

- [ ] **Step 5: Commit**

```bash
git add app/Views/projects/show.php public/assets/css/app.css
git commit -m "$(cat <<'EOF'
feat: show transaction progress and history on project detail

EOF
)"
```

---

### Task 7: Dokumentasi produksi (opsional)

**Files:**
- Modify: `produksi/README.md`
- Run: `bash scripts/build-produksi.sh` (zip tidak di-commit)

- [ ] **Step 1:** Tambah paragraf fitur transaksi (setor, pengembalian terpisah, auto selesai)
- [ ] **Step 2:** Rebuild zip jika user butuh upload
- [ ] **Step 3: Commit README**

```bash
git add produksi/README.md
git commit -m "$(cat <<'EOF'
docs: document investor transactions in production README

EOF
)"
```

---

## Spec coverage

| Spec | Task |
|------|------|
| Tabel + model | 1–2 |
| Progress / remaining / zero profit | 3 |
| Store/delete + auto status | 4–5 |
| Kunci edit | 4–5 |
| Hapus Tandai Selesai + UI rekapan | 6 |
| Tanpa operator TX / dashboard global | out of scope |
| README produksi | 7 |

## Nama konsisten

- Jenis: `setor_modal`, `pengembalian_modal`, `pengembalian_profit`
- Progress keys: `setor` / `modal` / `profit` → `{target,sudah,sisa,persen}`
- Methods: `buildProgress`, `canRecord`, `resolveStatusPayload`, `syncProjectSettlement`
