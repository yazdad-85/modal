<?php

namespace Tests\Feature;

use App\Models\InvestorModel;
use App\Models\ProjectModel;
use App\Models\TransactionModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Tests\Support\FeatureTestCase;

final class ProjectTest extends FeatureTestCase
{
    public function testCreateProject(): void
    {
        $this->loginAsUser();

        $result = $this->postWithCsrf('projects', [
            'nama_proyek'     => 'Proyek Serer',
            'mode_input'      => 'direct',
            'total_modal'     => 350_000_000,
            'total_hasil_jual'=> 420_000_000,
            'persen_pemodal'  => 60,
            'persen_operator' => 40,
            'nama_operator'   => 'Operator A',
            'waktu_kontrak'   => '3 bulan',
            'investor_nama'   => ['Pemodal A', 'Pemodal B'],
            'investor_modal'  => [150_000_000, 200_000_000],
        ]);

        $result->assertRedirect();
        $this->assertMatchesRegularExpression('#/projects/\d+#', $result->getRedirectUrl());

        preg_match('#/projects/(\d+)#', $result->getRedirectUrl(), $matches);
        $projectId = (int) $matches[1];
        $this->assertSame('3 bulan', (new ProjectModel())->find($projectId)['waktu_kontrak']);

        $this->get('projects/' . $projectId)->assertSee('Waktu Kontrak Proyek');
        $this->get('projects/' . $projectId)->assertSee('3 bulan');
    }

    public function testCannotAccessOtherUsersProject(): void
    {
        $this->loginAsUser('User A', 'usera@example.com');
        $create = $this->postWithCsrf('projects', [
            'nama_proyek'     => 'Proyek Privat',
            'mode_input'      => 'direct',
            'total_modal'     => 100_000_000,
            'total_hasil_jual'=> 120_000_000,
            'persen_pemodal'  => 60,
            'persen_operator' => 40,
            'nama_operator'   => 'Operator A',
            'investor_nama'   => ['Pemodal A'],
            'investor_modal'  => [100_000_000],
        ]);

        preg_match('#/projects/(\d+)#', $create->getRedirectUrl(), $matches);
        $projectId = (int) $matches[1];

        $this->get('logout');

        $this->loginAsUser('User B', 'userb@example.com');

        $this->expectException(PageNotFoundException::class);

        $this->get('projects/' . $projectId);
    }

    public function testCannotEditCompletedProject(): void
    {
        $this->loginAsUser();
        $create = $this->postWithCsrf('projects', [
            'nama_proyek'      => 'Proyek Locked',
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
        $investorId = (int) $investors[0]['id'];

        // Settle via full transactions (modal 100jt, hasil 120jt, 60/40 → profit 12jt)
        foreach (
            [
                [TransactionModel::JENIS_SETOR, 100_000_000],
                [TransactionModel::JENIS_PENGEMBALIAN_MODAL, 100_000_000],
                [TransactionModel::JENIS_PENGEMBALIAN_PROFIT, 12_000_000],
            ] as [$jenis, $jumlah]
        ) {
            $this->postWithCsrf('projects/' . $projectId . '/transactions', [
                'jenis'       => $jenis,
                'tanggal'     => '2026-07-13',
                'investor_id' => $investorId,
                'jumlah'      => $jumlah,
                'catatan'     => '',
            ]);
        }

        $this->assertSame('completed', (new ProjectModel())->find($projectId)['status']);

        $edit = $this->get('projects/' . $projectId . '/edit');
        $edit->assertRedirectTo('/projects/' . $projectId);
    }
}
