<?php

namespace App\Controllers;

use App\Libraries\ProfitCalculator;
use App\Libraries\TransactionService;
use App\Models\InvestorModel;
use App\Models\OperationalCostModel;
use App\Models\ProjectModel;
use App\Models\TransactionModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use InvalidArgumentException;

class ProjectController extends BaseController
{
    protected ProjectModel $projectModel;
    protected InvestorModel $investorModel;
    protected OperationalCostModel $operationalCostModel;
    protected TransactionModel $transactionModel;
    protected ProfitCalculator $calculator;
    protected TransactionService $transactionService;

    public function __construct()
    {
        $this->projectModel          = new ProjectModel();
        $this->investorModel         = new InvestorModel();
        $this->operationalCostModel  = new OperationalCostModel();
        $this->transactionModel      = new TransactionModel();
        $this->calculator            = new ProfitCalculator();
        $this->transactionService    = new TransactionService();
    }

    public function create()
    {
        helper(['form', 'url', 'rupiah']);

        return view('projects/create', [
            'project'           => null,
            'investors'         => [['nama' => '', 'modal' => '']],
            'operationalCosts'  => [],
        ]);
    }

    public function store()
    {
        $built = $this->buildProjectPayload();

        if ($built === null) {
            return redirect()->back()->withInput();
        }

        try {
            $this->runCalculation(
                $built['project'],
                $built['investors'],
                $built['operational_costs']
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        $projectId = $this->projectModel->insert($built['project']);

        if ($projectId === false) {
            return redirect()->back()->withInput()
                ->with('error', 'Gagal menyimpan proyek. Silakan coba lagi.');
        }

        $this->investorModel->syncForProject((int) $projectId, $built['investors']);
        $this->operationalCostModel->syncForProject((int) $projectId, $built['operational_costs']);

        return redirect()->to('/projects/' . $projectId)
            ->with('success', 'Proyek berhasil disimpan.');
    }

    public function show(int $id)
    {
        helper(['form', 'url', 'rupiah']);

        $project = $this->findOwnedProject($id);
        $investors = $this->investorModel->getByProject($id);
        $operationalCosts = $this->operationalCostModel->getByProject($id);
        $transactions = $this->transactionModel->getByProject($id);
        $hasTransactions = $this->transactionModel->countByProject($id) > 0;

        try {
            $result = $this->runCalculation($project, $investors, $operationalCosts);
        } catch (InvalidArgumentException $e) {
            return redirect()->to('/dashboard')->with('error', $e->getMessage());
        }

        $sums = $this->transactionModel->sumsGroupedByInvestor($id);
        $progress = $this->transactionService->buildProgress($investors, $result, $sums);

        $investorNames = [];
        foreach ($investors as $investor) {
            $investorNames[(int) $investor['id']] = $investor['nama'];
        }

        return view('projects/show', [
            'project'           => $project,
            'investors'         => $investors,
            'operationalCosts'  => $operationalCosts,
            'result'            => $result,
            'progress'          => $progress,
            'transactions'      => $transactions,
            'investorNames'     => $investorNames,
            'hasTransactions'   => $hasTransactions,
        ]);
    }

    public function edit(int $id)
    {
        helper(['form', 'url', 'rupiah']);

        $project = $this->findOwnedProject($id);

        if ($this->projectModel->isCompleted($project)) {
            return redirect()->to('/projects/' . $id)
                ->with('error', 'Proyek selesai tidak dapat diedit.');
        }

        if ($this->transactionModel->countByProject($id) > 0) {
            return redirect()->to('/projects/' . $id)
                ->with('error', 'Proyek yang sudah memiliki transaksi tidak dapat diedit.');
        }

        $investors = $this->investorModel->getByProject($id);
        $operationalCosts = $this->operationalCostModel->getByProject($id);

        if ($investors === []) {
            $investors = [['nama' => '', 'modal' => '']];
        }

        return view('projects/edit', [
            'project'           => $project,
            'investors'         => $investors,
            'operationalCosts'  => $operationalCosts,
        ]);
    }

    public function update(int $id)
    {
        $project = $this->findOwnedProject($id);

        if ($this->projectModel->isCompleted($project)) {
            return redirect()->to('/projects/' . $id)
                ->with('error', 'Proyek selesai tidak dapat diedit.');
        }

        if ($this->transactionModel->countByProject($id) > 0) {
            return redirect()->to('/projects/' . $id)
                ->with('error', 'Proyek yang sudah memiliki transaksi tidak dapat diedit.');
        }

        $built = $this->buildProjectPayload();

        if ($built === null) {
            return redirect()->back()->withInput();
        }

        try {
            $this->runCalculation(
                $built['project'],
                $built['investors'],
                $built['operational_costs']
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        $built['project']['user_id'] = (int) session('user_id');

        if (! $this->projectModel->update($id, $built['project'])) {
            return redirect()->back()->withInput()
                ->with('error', 'Gagal memperbarui proyek. Silakan coba lagi.');
        }

        $this->investorModel->syncForProject($id, $built['investors']);
        $this->operationalCostModel->syncForProject($id, $built['operational_costs']);

        return redirect()->to('/projects/' . $id)
            ->with('success', 'Proyek berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        $this->findOwnedProject($id);

        $this->projectModel->delete($id);

        return redirect()->to('/dashboard')
            ->with('success', 'Proyek berhasil dihapus.');
    }

    public function storeTransaction(int $id)
    {
        $project = $this->findOwnedProject($id);

        if ($this->projectModel->isCompleted($project) && $this->transactionModel->countByProject($id) === 0) {
            return redirect()->to('/projects/' . $id)
                ->with('error', 'Proyek selesai (lama) tidak dapat menambah transaksi.');
        }

        $investors = $this->investorModel->getByProject($id);
        $operationalCosts = $this->operationalCostModel->getByProject($id);

        $jenis = (string) $this->request->getPost('jenis');
        $tanggal = (string) $this->request->getPost('tanggal');
        $investorId = (int) $this->request->getPost('investor_id');
        $jumlah = $this->parseAmount($this->request->getPost('jumlah'));
        $catatan = trim((string) $this->request->getPost('catatan'));

        if (! in_array($jenis, TransactionModel::JENIS_LIST, true)) {
            return redirect()->to('/projects/' . $id)
                ->with('error', 'Jenis transaksi tidak valid.');
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $tanggal);
        if ($date === false || $date->format('Y-m-d') !== $tanggal) {
            return redirect()->to('/projects/' . $id)
                ->with('error', 'Tanggal transaksi tidak valid.');
        }

        $investorIds = array_map(static fn (array $i): int => (int) $i['id'], $investors);
        if (! in_array($investorId, $investorIds, true)) {
            return redirect()->to('/projects/' . $id)
                ->with('error', 'Pemodal tidak ditemukan pada proyek ini.');
        }

        try {
            $result = $this->runCalculation($project, $investors, $operationalCosts);
        } catch (InvalidArgumentException $e) {
            return redirect()->to('/projects/' . $id)->with('error', $e->getMessage());
        }

        $sums = $this->transactionModel->sumsGroupedByInvestor($id);
        $progress = $this->transactionService->buildProgress($investors, $result, $sums);

        $progressRow = null;
        foreach ($progress['investors'] as $row) {
            if ((int) $row['investor_id'] === $investorId) {
                $progressRow = $row;
                break;
            }
        }

        if ($progressRow === null) {
            return redirect()->to('/projects/' . $id)
                ->with('error', 'Pemodal tidak ditemukan pada proyek ini.');
        }

        $target = $this->transactionService->targetForJenis($progressRow, $jenis);
        $sudah  = $this->transactionService->sudahForJenis($progressRow, $jenis);

        if (! $this->transactionService->canRecord($target, $sudah, $jumlah)) {
            return redirect()->to('/projects/' . $id)
                ->with('error', 'Jumlah melebihi sisa target atau tidak valid.');
        }

        $inserted = $this->transactionModel->insert([
            'project_id'  => $id,
            'investor_id' => $investorId,
            'jenis'       => $jenis,
            'jumlah'      => $jumlah,
            'tanggal'     => $tanggal,
            'catatan'     => $catatan !== '' ? $catatan : null,
            'created_by'  => (int) session('user_id'),
        ]);

        if ($inserted === false) {
            return redirect()->to('/projects/' . $id)
                ->with('error', 'Gagal menyimpan transaksi. Silakan coba lagi.');
        }

        $sumsAfter = $this->transactionModel->sumsGroupedByInvestor($id);
        $progressAfter = $this->transactionService->buildProgress($investors, $result, $sumsAfter);
        if (! $this->syncProjectSettlement($id, $project, $progressAfter['is_fully_settled'])) {
            return redirect()->to('/projects/' . $id)
                ->with('error', 'Transaksi tersimpan, tetapi gagal memperbarui status proyek.');
        }

        return redirect()->to('/projects/' . $id)
            ->with('success', 'Transaksi berhasil dicatat.');
    }

    public function deleteTransaction(int $id, int $transactionId)
    {
        $project = $this->findOwnedProject($id);
        $transaction = $this->transactionModel->find($transactionId);

        if ($transaction === null || (int) $transaction['project_id'] !== $id) {
            throw PageNotFoundException::forPageNotFound();
        }

        $investors = $this->investorModel->getByProject($id);
        $operationalCosts = $this->operationalCostModel->getByProject($id);

        try {
            $result = $this->runCalculation($project, $investors, $operationalCosts);
        } catch (InvalidArgumentException $e) {
            return redirect()->to('/projects/' . $id)->with('error', $e->getMessage());
        }

        $this->transactionModel->delete($transactionId);

        $sums = $this->transactionModel->sumsGroupedByInvestor($id);
        $progress = $this->transactionService->buildProgress($investors, $result, $sums);
        if (! $this->syncProjectSettlement($id, $project, $progress['is_fully_settled'])) {
            return redirect()->to('/projects/' . $id)
                ->with('error', 'Transaksi terhapus, tetapi gagal memperbarui status proyek.');
        }

        return redirect()->to('/projects/' . $id)
            ->with('success', 'Transaksi berhasil dihapus.');
    }

    /**
     * @param array<string, mixed> $project
     */
    private function syncProjectSettlement(int $projectId, array $project, bool $fullySettled): bool
    {
        $isCompleted = $this->projectModel->isCompleted($project);

        if ($fullySettled && ! $isCompleted) {
            return $this->projectModel->update($projectId, [
                'status'       => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
            ]) !== false;
        }

        if (! $fullySettled && $isCompleted) {
            return $this->projectModel->update($projectId, [
                'status'       => 'active',
                'completed_at' => null,
            ]) !== false;
        }

        // Already completed and still settled: do not overwrite completed_at.
        return true;
    }

    private function findOwnedProject(int $id): array
    {
        $project = $this->projectModel->findForUser($id, (int) session('user_id'));

        if ($project === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $project;
    }

    /**
     * @return array{project: array<string, mixed>, investors: list<array{nama: string, modal: int}>}|null
     */
    private function buildProjectPayload(): ?array
    {
        $rules = [
            'nama_proyek'      => 'required|min_length[2]|max_length[200]',
            'mode_input'       => 'required|in_list[unit,direct]',
            'nama_operator'    => 'required|min_length[2]|max_length[100]',
            'persen_pemodal'   => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
            'persen_operator'  => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
            'catatan'          => 'permit_empty|max_length[5000]',
        ];

        $mode = $this->request->getPost('mode_input');

        if ($mode === 'unit') {
            $rules['jumlah_unit'] = 'required|integer|greater_than[0]';
            $rules['harga_beli']  = 'required|integer|greater_than[0]|less_than_equal_to[999999999999]';
            $rules['harga_jual']  = 'required|integer|greater_than[0]|less_than_equal_to[999999999999]';
        } else {
            $rules['total_modal']      = 'required|integer|greater_than[0]|less_than_equal_to[999999999999]';
            $rules['total_hasil_jual'] = 'required|integer|greater_than[0]|less_than_equal_to[999999999999]';
        }

        if (! $this->validate($rules, $this->validationMessages())) {
            session()->setFlashdata('errors', $this->validator->getErrors());

            return null;
        }

        $persenPemodal  = (float) $this->request->getPost('persen_pemodal');
        $persenOperator = (float) $this->request->getPost('persen_operator');

        if (round($persenPemodal + $persenOperator, 2) !== 100.0) {
            session()->setFlashdata('error', 'Persentase pemodal dan operator harus berjumlah 100%');

            return null;
        }

        $investors = $this->parseInvestors();

        if ($investors === null) {
            return null;
        }

        $operationalCosts = $this->parseOperationalCosts();

        if ($operationalCosts === null) {
            return null;
        }

        if ($mode === 'unit') {
            $jumlahUnit = (int) $this->request->getPost('jumlah_unit');
            $hargaBeli  = $this->parseAmount($this->request->getPost('harga_beli'));
            $hargaJual  = $this->parseAmount($this->request->getPost('harga_jual'));

            $totals = $this->calculator->computeUnitTotals($jumlahUnit, $hargaBeli, $hargaJual);

            $projectData = [
                'user_id'          => (int) session('user_id'),
                'nama_proyek'      => trim((string) $this->request->getPost('nama_proyek')),
                'mode_input'       => 'unit',
                'jumlah_unit'      => $jumlahUnit,
                'harga_beli'       => $hargaBeli,
                'harga_jual'       => $hargaJual,
                'total_modal'      => $totals['total_modal'],
                'total_hasil_jual' => $totals['total_hasil_jual'],
                'persen_pemodal'   => $persenPemodal,
                'persen_operator'  => $persenOperator,
                'nama_operator'    => trim((string) $this->request->getPost('nama_operator')),
                'catatan'          => trim((string) $this->request->getPost('catatan')) ?: null,
            ];
        } else {
            $projectData = [
                'user_id'          => (int) session('user_id'),
                'nama_proyek'      => trim((string) $this->request->getPost('nama_proyek')),
                'mode_input'       => 'direct',
                'jumlah_unit'      => null,
                'harga_beli'       => null,
                'harga_jual'       => null,
                'total_modal'      => $this->parseAmount($this->request->getPost('total_modal')),
                'total_hasil_jual' => $this->parseAmount($this->request->getPost('total_hasil_jual')),
                'persen_pemodal'   => $persenPemodal,
                'persen_operator'  => $persenOperator,
                'nama_operator'    => trim((string) $this->request->getPost('nama_operator')),
                'catatan'          => trim((string) $this->request->getPost('catatan')) ?: null,
            ];
        }

        return [
            'project'            => $projectData,
            'investors'          => $investors,
            'operational_costs'  => $operationalCosts,
        ];
    }

    /**
     * @param array<string, mixed> $project
     * @param list<array<string, mixed>> $investors
     * @param list<array<string, mixed>> $operationalCosts
     *
     * @return array<string, mixed>
     */
    private function runCalculation(array $project, array $investors, array $operationalCosts): array
    {
        return $this->calculator->calculate(
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
    }

    /**
     * @return list<array{nama: string, modal: int}>|null
     */
    private function parseInvestors(): ?array
    {
        $names   = $this->request->getPost('investor_nama');
        $amounts = $this->request->getPost('investor_modal');

        if (! is_array($names) || ! is_array($amounts)) {
            session()->setFlashdata('error', 'Data pemodal tidak valid.');

            return null;
        }

        $investors = [];

        foreach ($names as $index => $name) {
            $nama  = trim((string) $name);
            $modal = $this->parseAmount($amounts[$index] ?? 0);

            if ($nama === '' && $modal === 0) {
                continue;
            }

            if ($nama === '') {
                session()->setFlashdata('error', 'Nama pemodal wajib diisi.');

                return null;
            }

            if ($modal <= 0) {
                session()->setFlashdata('error', 'Nominal modal pemodal harus lebih dari 0.');

                return null;
            }

            if (strlen($nama) > 100) {
                session()->setFlashdata('error', 'Nama pemodal maksimal 100 karakter.');

                return null;
            }

            $investors[] = [
                'nama'  => $nama,
                'modal' => $modal,
            ];
        }

        if ($investors === []) {
            session()->setFlashdata('error', 'Minimal satu pemodal diperlukan.');

            return null;
        }

        return $investors;
    }

    /**
     * @return list<array{keterangan: string, jumlah: int}>|null
     */
    private function parseOperationalCosts(): ?array
    {
        $labels  = $this->request->getPost('ops_keterangan');
        $amounts = $this->request->getPost('ops_jumlah');

        if ($labels === null && $amounts === null) {
            return [];
        }

        if (! is_array($labels) || ! is_array($amounts)) {
            session()->setFlashdata('error', 'Data biaya operasional tidak valid.');

            return null;
        }

        $costs = [];

        foreach ($labels as $index => $label) {
            $keterangan = trim((string) $label);
            $jumlah     = $this->parseAmount($amounts[$index] ?? 0);

            if ($keterangan === '' && $jumlah === 0) {
                continue;
            }

            if ($keterangan === '') {
                session()->setFlashdata('error', 'Keterangan biaya operasional wajib diisi.');

                return null;
            }

            if ($jumlah <= 0) {
                session()->setFlashdata('error', 'Jumlah biaya operasional harus lebih dari 0.');

                return null;
            }

            if (strlen($keterangan) > 200) {
                session()->setFlashdata('error', 'Keterangan biaya operasional maksimal 200 karakter.');

                return null;
            }

            $costs[] = [
                'keterangan' => $keterangan,
                'jumlah'     => $jumlah,
            ];
        }

        return $costs;
    }

    private function parseAmount(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $cleaned = preg_replace('/[^\d]/', '', (string) $value);

        return (int) $cleaned;
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'nama_proyek' => [
                'required'   => 'Nama proyek wajib diisi.',
                'min_length' => 'Nama proyek minimal 2 karakter.',
                'max_length' => 'Nama proyek maksimal 200 karakter.',
            ],
            'mode_input' => [
                'required' => 'Mode input wajib dipilih.',
                'in_list'  => 'Mode input tidak valid.',
            ],
            'nama_operator' => [
                'required'   => 'Nama operator wajib diisi.',
                'min_length' => 'Nama operator minimal 2 karakter.',
                'max_length' => 'Nama operator maksimal 100 karakter.',
            ],
            'jumlah_unit' => [
                'required'     => 'Jumlah unit wajib diisi.',
                'integer'      => 'Jumlah unit harus berupa angka bulat.',
                'greater_than' => 'Jumlah unit harus lebih dari 0.',
            ],
            'harga_beli' => [
                'required'     => 'Harga beli wajib diisi.',
                'integer'      => 'Harga beli harus berupa angka bulat.',
                'greater_than' => 'Harga beli harus lebih dari 0.',
            ],
            'harga_jual' => [
                'required'     => 'Harga jual wajib diisi.',
                'integer'      => 'Harga jual harus berupa angka bulat.',
                'greater_than' => 'Harga jual harus lebih dari 0.',
            ],
            'total_modal' => [
                'required'     => 'Total modal wajib diisi.',
                'integer'      => 'Total modal harus berupa angka bulat.',
                'greater_than' => 'Total modal harus lebih dari 0.',
            ],
            'total_hasil_jual' => [
                'required'     => 'Total hasil jual wajib diisi.',
                'integer'      => 'Total hasil jual harus berupa angka bulat.',
                'greater_than' => 'Total hasil jual harus lebih dari 0.',
            ],
            'persen_pemodal' => [
                'required' => 'Persentase pemodal wajib diisi.',
            ],
            'persen_operator' => [
                'required' => 'Persentase operator wajib diisi.',
            ],
        ];
    }
}
