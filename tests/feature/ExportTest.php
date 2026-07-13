<?php

namespace Tests\Feature;

use App\Models\InvestorModel;
use App\Models\TransactionModel;
use CodeIgniter\Security\Exceptions\SecurityException;
use Tests\Support\FeatureTestCase;

final class ExportTest extends FeatureTestCase
{
    public function testPdfExportRequiresAuth(): void
    {
        $result = $this->get('projects/1/export/pdf');

        $result->assertRedirectTo('/login');
    }

    public function testCsrfRejection(): void
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionCode(403);

        $this->withHeaders(['X-CSRF-TOKEN' => 'invalid-token']);

        $this->post('login', [
            'email'    => 'test@example.com',
            'password' => 'password1',
        ]);
    }

    /**
     * @return array{projectId: int, investorId: int}
     */
    private function createSoloProjectWithSetor(): array
    {
        $create = $this->postWithCsrf('projects', [
            'nama_proyek'      => 'Proyek Export Tx',
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

        $investors  = (new InvestorModel())->getByProject($projectId);
        $investorId = (int) $investors[0]['id'];

        $this->postWithCsrf('projects/' . $projectId . '/transactions', [
            'jenis'       => TransactionModel::JENIS_SETOR,
            'tanggal'     => '2026-07-13',
            'investor_id' => $investorId,
            'jumlah'      => 40_000_000,
            'catatan'     => 'Setoran awal',
        ]);

        return [
            'projectId'  => $projectId,
            'investorId' => $investorId,
        ];
    }

    public function testPdfExportIncludesTransactionSections(): void
    {
        $this->loginAsUser();
        ['projectId' => $projectId] = $this->createSoloProjectWithSetor();

        $result = $this->get('projects/' . $projectId . '/export/pdf');

        $result->assertOK();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $result->response()->getHeaderLine('Content-Type')
        );
        $this->assertNotSame('', $result->response()->getBody());
    }

    public function testExcelExportIncludesTransactionLabels(): void
    {
        $this->loginAsUser();
        ['projectId' => $projectId] = $this->createSoloProjectWithSetor();

        $result = $this->get('projects/' . $projectId . '/export/excel');

        $result->assertOK();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            (string) $result->response()->getHeaderLine('Content-Type')
        );

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $this->assertNotFalse($tmp);
        file_put_contents($tmp, $result->response()->getBody());

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($tmp) === true);
        $shared = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();
        unlink($tmp);

        $this->assertNotFalse($shared);
        $this->assertStringContainsString('Progress Transaksi', $shared);
        $this->assertStringContainsString('Riwayat Transaksi', $shared);
        $this->assertStringContainsString('Setor modal', $shared);
        $this->assertStringContainsString('Pemodal A', $shared);
        $this->assertStringContainsString('Setoran awal', $shared);
    }

    public function testPdfTemplateRendersTransactionHistory(): void
    {
        helper('rupiah');

        $html = view('exports/pdf_template', [
            'project' => [
                'nama_proyek'     => 'Demo',
                'nama_operator'   => 'Op',
                'mode_input'      => 'direct',
                'persen_pemodal'  => 60,
                'persen_operator' => 40,
                'catatan'         => '',
            ],
            'result' => [
                'total_modal'              => 100,
                'total_hasil_jual'         => 120,
                'rugi'                     => false,
                'keuntungan_kotor'         => 20,
                'total_biaya_operasional'  => 0,
                'keuntungan_bersih'        => 20,
                'profit_dapat_dibagikan'   => true,
                'pool_pemodal'             => 12,
                'pool_operator'            => 8,
                'biaya_operasional'        => [],
                'investors'                => [
                    [
                        'nama'               => 'Pemodal A',
                        'modal'              => 100,
                        'pengembalian_modal' => 100,
                        'profit'             => 12,
                        'total'              => 112,
                    ],
                ],
            ],
            'progress' => [
                'is_fully_settled' => false,
                'project'          => [
                    'setor'  => ['sudah' => 40, 'target' => 100, 'sisa' => 60, 'persen' => 40],
                    'modal'  => ['sudah' => 0, 'target' => 100, 'sisa' => 100, 'persen' => 0],
                    'profit' => ['sudah' => 0, 'target' => 12, 'sisa' => 12, 'persen' => 0],
                ],
                'investors' => [
                    [
                        'nama'    => 'Pemodal A',
                        'settled' => false,
                        'setor'   => ['sudah' => 40, 'target' => 100, 'sisa' => 60, 'persen' => 40],
                        'modal'   => ['sudah' => 0, 'target' => 100, 'sisa' => 100, 'persen' => 0],
                        'profit'  => ['sudah' => 0, 'target' => 12, 'sisa' => 12, 'persen' => 0],
                    ],
                ],
            ],
            'transactions' => [
                [
                    'tanggal'     => '2026-07-13',
                    'investor_id' => 1,
                    'jenis'       => TransactionModel::JENIS_SETOR,
                    'jumlah'      => 40,
                    'catatan'     => 'Setoran awal',
                ],
            ],
            'investorNames' => [1 => 'Pemodal A'],
            'jenisLabel'    => [
                TransactionModel::JENIS_SETOR => 'Setor modal',
            ],
        ]);

        $this->assertStringContainsString('Progress Transaksi', $html);
        $this->assertStringContainsString('Riwayat Transaksi', $html);
        $this->assertStringContainsString('Setor modal', $html);
        $this->assertStringContainsString('Setoran awal', $html);
        $this->assertStringContainsString('Pemodal A', $html);
    }
}
