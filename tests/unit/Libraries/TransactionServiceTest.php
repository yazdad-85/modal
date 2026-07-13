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
