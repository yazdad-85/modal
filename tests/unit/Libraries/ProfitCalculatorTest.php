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

    public function testOperationalCostsReduceProfit(): void
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
            ],
            operationalCosts: [
                ['keterangan' => 'Transport', 'jumlah' => 10_000_000],
                ['keterangan' => 'Gaji', 'jumlah' => 5_000_000],
            ]
        );

        $this->assertSame(70_000_000, $result['keuntungan_kotor']);
        $this->assertSame(15_000_000, $result['total_biaya_operasional']);
        $this->assertSame(55_000_000, $result['keuntungan_bersih']);
        $this->assertTrue($result['profit_dapat_dibagikan']);
        $this->assertSame(33_000_000, $result['pool_pemodal']);
        $this->assertSame(22_000_000, $result['pool_operator']);
        $this->assertSame(4_714_286, $result['investors'][0]['profit']);
    }

    public function testOperationalCostsExceedGrossProfit(): void
    {
        $result = $this->calculator->calculate(
            totalHasilJual: 420_000_000,
            totalModal: 350_000_000,
            persenPemodal: 60,
            persenOperator: 40,
            investors: [['nama' => 'A', 'modal' => 350_000_000]],
            operationalCosts: [
                ['keterangan' => 'Operasional', 'jumlah' => 80_000_000],
            ]
        );

        $this->assertSame(-10_000_000, $result['keuntungan_bersih']);
        $this->assertFalse($result['profit_dapat_dibagikan']);
        $this->assertSame(0, $result['pool_pemodal']);
        $this->assertSame(0, $result['pool_operator']);
    }

    public function testComputeTotalsFromUnitMode(): void
    {
        $totals = $this->calculator->computeUnitTotals(23, 24_000_000, 30_000_000);

        $this->assertSame(552_000_000, $totals['total_modal']);
        $this->assertSame(690_000_000, $totals['total_hasil_jual']);
    }
}
