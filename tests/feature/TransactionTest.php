<?php

namespace Tests\Feature;

use App\Models\InvestorModel;
use App\Models\ProjectModel;
use App\Models\TransactionModel;
use Tests\Support\FeatureTestCase;

final class TransactionTest extends FeatureTestCase
{
    /**
     * @return array{projectId: int, investorId: int}
     */
    private function createSoloProject(): array
    {
        $create = $this->postWithCsrf('projects', [
            'nama_proyek'      => 'Proyek Transaksi',
            'mode_input'       => 'direct',
            'total_modal'      => 100_000_000,
            'total_hasil_jual' => 120_000_000,
            'persen_pemodal'   => 60,
            'persen_operator'  => 40,
            'nama_operator'    => 'Operator A',
            'investor_nama'    => ['Pemodal A'],
            'investor_modal'   => [100_000_000],
        ]);

        preg_match('#/projects/(\d+)#', $create->getRedirectUrl(), $matches);
        $projectId = (int) $matches[1];

        $investors = (new InvestorModel())->getByProject($projectId);

        return [
            'projectId'  => $projectId,
            'investorId' => (int) $investors[0]['id'],
        ];
    }

    private function postTransaction(
        int $projectId,
        int $investorId,
        string $jenis,
        int $jumlah
    ): void {
        $this->postWithCsrf('projects/' . $projectId . '/transactions', [
            'jenis'       => $jenis,
            'tanggal'     => '2026-07-13',
            'investor_id' => $investorId,
            'jumlah'      => $jumlah,
            'catatan'     => '',
        ]);
    }

    private function settleFully(int $projectId, int $investorId): void
    {
        // modal 100jt, hasil 120jt, 60/40 → profit solo = 12_000_000
        $this->postTransaction($projectId, $investorId, TransactionModel::JENIS_SETOR, 100_000_000);
        $this->postTransaction($projectId, $investorId, TransactionModel::JENIS_PENGEMBALIAN_MODAL, 100_000_000);
        $this->postTransaction($projectId, $investorId, TransactionModel::JENIS_PENGEMBALIAN_PROFIT, 12_000_000);
    }

    public function testStorePartialSetor(): void
    {
        $this->loginAsUser();
        ['projectId' => $projectId, 'investorId' => $investorId] = $this->createSoloProject();

        $this->postTransaction($projectId, $investorId, TransactionModel::JENIS_SETOR, 40_000_000);

        $this->assertSame(1, (new TransactionModel())->countByProject($projectId));
        $project = (new ProjectModel())->find($projectId);
        $this->assertSame('active', $project['status']);
    }

    public function testRejectOverpay(): void
    {
        $this->loginAsUser();
        ['projectId' => $projectId, 'investorId' => $investorId] = $this->createSoloProject();

        $this->postTransaction($projectId, $investorId, TransactionModel::JENIS_SETOR, 100_000_001);

        $this->assertSame(0, (new TransactionModel())->countByProject($projectId));
    }

    public function testAutoCompleteWhenFullySettled(): void
    {
        $this->loginAsUser();
        ['projectId' => $projectId, 'investorId' => $investorId] = $this->createSoloProject();

        $this->settleFully($projectId, $investorId);

        $project = (new ProjectModel())->find($projectId);
        $this->assertSame('completed', $project['status']);
        $this->assertSame(3, (new TransactionModel())->countByProject($projectId));
    }

    public function testDeleteReopensCompletedProject(): void
    {
        $this->loginAsUser();
        ['projectId' => $projectId, 'investorId' => $investorId] = $this->createSoloProject();

        $this->settleFully($projectId, $investorId);

        $transactions = (new TransactionModel())->getByProject($projectId);
        $this->assertNotEmpty($transactions);
        $txId = (int) $transactions[0]['id'];

        $this->postWithCsrf(
            'projects/' . $projectId . '/transactions/' . $txId . '/delete',
            []
        );

        $project = (new ProjectModel())->find($projectId);
        $this->assertSame('active', $project['status']);
        $this->assertSame(2, (new TransactionModel())->countByProject($projectId));
    }

    public function testEditBlockedAfterTransaction(): void
    {
        $this->loginAsUser();
        ['projectId' => $projectId, 'investorId' => $investorId] = $this->createSoloProject();

        $this->postTransaction($projectId, $investorId, TransactionModel::JENIS_SETOR, 10_000_000);

        $edit = $this->get('projects/' . $projectId . '/edit');
        $edit->assertRedirectTo('/projects/' . $projectId);
    }
}
