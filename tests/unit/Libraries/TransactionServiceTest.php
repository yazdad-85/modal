<?php

namespace Tests\Unit\Libraries;

use App\Libraries\ProfitCalculator;
use App\Libraries\TransactionService;
use App\Models\TransactionModel;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

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

    public function testRemaining(): void
    {
        $this->assertSame(10, $this->service->remaining(100, 90));
        $this->assertSame(0, $this->service->remaining(100, 100));
        $this->assertSame(0, $this->service->remaining(100, 110));
    }

    public function testCanRecord(): void
    {
        $this->assertFalse($this->service->canRecord(100, 90, 20));
        $this->assertTrue($this->service->canRecord(100, 90, 10));
        $this->assertFalse($this->service->canRecord(100, 90, 0));
        $this->assertFalse($this->service->canRecord(100, 90, -5));
    }

    public function testPercentEdgeCases(): void
    {
        $this->assertSame(100, $this->service->percent(0, 0));
        $this->assertSame(50, $this->service->percent(100, 50));
    }

    public function testBuildProgressThrowsWhenCalcInvestorsShorter(): void
    {
        $investors = [
            ['id' => 1, 'nama' => 'A', 'modal' => 100_000_000],
            ['id' => 2, 'nama' => 'B', 'modal' => 50_000_000],
        ];
        $calc = [
            'investors' => [
                ['pengembalian_modal' => 100_000_000, 'profit' => 0],
            ],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hasil kalkulasi tidak selaras dengan daftar pemodal.');

        $this->service->buildProgress($investors, $calc, []);
    }

    public function testTargetAndSudahForJenis(): void
    {
        $row = [
            'setor'  => ['target' => 100, 'sudah' => 40],
            'modal'  => ['target' => 200, 'sudah' => 50],
            'profit' => ['target' => 300, 'sudah' => 60],
        ];

        $this->assertSame(100, $this->service->targetForJenis($row, TransactionModel::JENIS_SETOR));
        $this->assertSame(40, $this->service->sudahForJenis($row, TransactionModel::JENIS_SETOR));

        $this->assertSame(200, $this->service->targetForJenis($row, TransactionModel::JENIS_PENGEMBALIAN_MODAL));
        $this->assertSame(50, $this->service->sudahForJenis($row, TransactionModel::JENIS_PENGEMBALIAN_MODAL));

        $this->assertSame(300, $this->service->targetForJenis($row, TransactionModel::JENIS_PENGEMBALIAN_PROFIT));
        $this->assertSame(60, $this->service->sudahForJenis($row, TransactionModel::JENIS_PENGEMBALIAN_PROFIT));
    }

    public function testTargetForJenisThrowsOnInvalidJenis(): void
    {
        $row = [
            'setor'  => ['target' => 100, 'sudah' => 40],
            'modal'  => ['target' => 200, 'sudah' => 50],
            'profit' => ['target' => 300, 'sudah' => 60],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Jenis transaksi tidak valid.');

        $this->service->targetForJenis($row, 'jenis_tidak_ada');
    }

    public function testSudahForJenisThrowsOnInvalidJenis(): void
    {
        $row = [
            'setor'  => ['target' => 100, 'sudah' => 40],
            'modal'  => ['target' => 200, 'sudah' => 50],
            'profit' => ['target' => 300, 'sudah' => 60],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Jenis transaksi tidak valid.');

        $this->service->sudahForJenis($row, 'jenis_tidak_ada');
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
