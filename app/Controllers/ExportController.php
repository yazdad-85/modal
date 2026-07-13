<?php

namespace App\Controllers;

use App\Libraries\ProfitCalculator;
use App\Models\InvestorModel;
use App\Models\OperationalCostModel;
use App\Models\ProjectModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportController extends BaseController
{
    public function pdf(int $id)
    {
        helper(['url', 'rupiah']);

        $data     = $this->getProjectData($id);
        $html     = view('exports/pdf_template', $data);
        $options  = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = $this->buildFilename($data['project']['nama_proyek'], 'pdf');

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }

    public function excel(int $id)
    {
        helper(['url', 'rupiah']);

        $data        = $this->getProjectData($id);
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan');

        $project = $data['project'];
        $result  = $data['result'];
        $isRugi  = $result['rugi'];

        $sheet->setCellValue('A1', 'ModalCalc — Laporan Proyek');
        $sheet->setCellValue('A2', $project['nama_proyek']);
        $sheet->setCellValue('A3', 'Operator: ' . $project['nama_operator']);
        $sheet->setCellValue('A4', 'Tanggal: ' . date('d/m/Y'));

        $row = 6;
        $sheet->setCellValue('A' . $row, 'Ringkasan');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $summary = [
            ['Total Modal', (int) $result['total_modal']],
            ['Total Hasil Jual', (int) $result['total_hasil_jual']],
            [$isRugi ? 'Rugi' : 'Profit Kotor', abs((int) $result['keuntungan_kotor'])],
            ['Total Biaya Operasional', (int) $result['total_biaya_operasional']],
            ['Profit Bersih', (int) $result['keuntungan_bersih']],
            ['Bagi Hasil Pemodal', (float) $project['persen_pemodal'] . '%'],
            ['Bagi Hasil Operator', (float) $project['persen_operator'] . '%'],
        ];

        if ($project['mode_input'] === 'unit') {
            $summary[] = ['Jumlah Unit', (int) $project['jumlah_unit'] . ' pcs'];
            $summary[] = ['Harga Beli / pcs', (int) $project['harga_beli']];
            $summary[] = ['Harga Jual / pcs', (int) $project['harga_jual']];
        }

        foreach ($summary as [$label, $value]) {
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('B' . $row, is_int($value) ? $value : $value);
            $row++;
        }

        if (! empty($project['catatan'])) {
            $row++;
            $sheet->setCellValue('A' . $row, 'Catatan');
            $sheet->setCellValue('B' . $row, $project['catatan']);
            $row++;
        }

        if ((int) $result['total_biaya_operasional'] > 0) {
            $row += 2;
            $sheet->setCellValue('A' . $row, 'Biaya Operasional');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            $sheet->fromArray(['Keterangan', 'Jumlah'], null, 'A' . $row);
            $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
            $row++;

            foreach ($result['biaya_operasional'] as $cost) {
                $sheet->fromArray([$cost['keterangan'], (int) $cost['jumlah']], null, 'A' . $row);
                $row++;
            }
        }

        $row += 2;
        $sheet->setCellValue('A' . $row, 'Pengembalian Modal');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $sheet->fromArray(['Pemodal', 'Modal', 'Pengembalian'], null, 'A' . $row);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE9ECEF');
        $row++;

        foreach ($result['investors'] as $investor) {
            $sheet->fromArray([
                $investor['nama'],
                (int) $investor['modal'],
                (int) $investor['pengembalian_modal'],
            ], null, 'A' . $row);
            $row++;
        }

        $row += 2;
        $sheet->setCellValue('A' . $row, 'Bagi Hasil Profit');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        if ($isRugi) {
            $sheet->setCellValue('A' . $row, 'Proyek mengalami rugi. Tidak ada profit yang dibagikan.');
            $row++;
        } elseif (! $result['profit_dapat_dibagikan']) {
            $sheet->setCellValue('A' . $row, 'Biaya operasional melebihi profit kotor. Tidak ada profit yang dibagikan.');
            $row++;
        } else {
            $sheet->setCellValue('A' . $row, 'Pool Pemodal (' . $project['persen_pemodal'] . '%)');
            $sheet->setCellValue('B' . $row, (int) $result['pool_pemodal']);
            $row++;
            $sheet->setCellValue('A' . $row, 'Pool Operator (' . $project['persen_operator'] . '%) — ' . $project['nama_operator']);
            $sheet->setCellValue('B' . $row, (int) $result['pool_operator']);
            $row += 2;

            $sheet->fromArray(['Pemodal', 'Profit'], null, 'A' . $row);
            $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':B' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE9ECEF');
            $row++;

            foreach ($result['investors'] as $investor) {
                $sheet->fromArray([$investor['nama'], (int) $investor['profit']], null, 'A' . $row);
                $row++;
            }
        }

        $row += 2;
        $sheet->setCellValue('A' . $row, 'Total per Pemodal');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $sheet->fromArray(['Pemodal', 'Pengembalian', 'Profit', 'Total'], null, 'A' . $row);
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':D' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE9ECEF');
        $row++;

        foreach ($result['investors'] as $investor) {
            $sheet->fromArray([
                $investor['nama'],
                (int) $investor['pengembalian_modal'],
                (int) $investor['profit'],
                (int) $investor['total'],
            ], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        $filename = $this->buildFilename($project['nama_proyek'], 'xlsx');

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($content);
    }

    /**
     * @return array{project: array<string, mixed>, investors: list<array<string, mixed>>, result: array<string, mixed>}
     */
    private function getProjectData(int $id): array
    {
        $projectModel = new ProjectModel();
        $project      = $projectModel->findForUser($id, (int) session('user_id'));

        if ($project === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $investors = (new InvestorModel())->getByProject($id);
        $operationalCosts = (new OperationalCostModel())->getByProject($id);
        $calculator = new ProfitCalculator();
        $result = $calculator->calculate(
            (int) $project['total_hasil_jual'],
            (int) $project['total_modal'],
            (float) $project['persen_pemodal'],
            (float) $project['persen_operator'],
            array_map(
                static fn (array $investor): array => [
                    'nama'  => $investor['nama'],
                    'modal' => (int) $investor['modal'],
                ],
                $investors
            ),
            array_map(
                static fn (array $cost): array => [
                    'keterangan' => $cost['keterangan'],
                    'jumlah'     => (int) $cost['jumlah'],
                ],
                $operationalCosts
            )
        );

        return compact('project', 'investors', 'operationalCosts', 'result');
    }

    private function buildFilename(string $projectName, string $extension): string
    {
        $slug = url_title($projectName, '-', true);

        if ($slug === '') {
            $slug = 'proyek';
        }

        return 'laporan-' . $slug . '-' . date('Ymd') . '.' . $extension;
    }
}
