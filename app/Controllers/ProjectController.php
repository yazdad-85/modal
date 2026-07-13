<?php

namespace App\Controllers;

use App\Libraries\ProfitCalculator;
use App\Models\InvestorModel;
use App\Models\OperationalCostModel;
use App\Models\ProjectModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use InvalidArgumentException;

class ProjectController extends BaseController
{
    protected ProjectModel $projectModel;
    protected InvestorModel $investorModel;
    protected OperationalCostModel $operationalCostModel;
    protected ProfitCalculator $calculator;

    public function __construct()
    {
        $this->projectModel          = new ProjectModel();
        $this->investorModel         = new InvestorModel();
        $this->operationalCostModel  = new OperationalCostModel();
        $this->calculator            = new ProfitCalculator();
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

        try {
            $result = $this->runCalculation($project, $investors, $operationalCosts);
        } catch (InvalidArgumentException $e) {
            return redirect()->to('/dashboard')->with('error', $e->getMessage());
        }

        return view('projects/show', [
            'project'           => $project,
            'investors'         => $investors,
            'operationalCosts'  => $operationalCosts,
            'result'            => $result,
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

    public function complete(int $id)
    {
        $project = $this->findOwnedProject($id);

        if ($this->projectModel->isCompleted($project)) {
            return redirect()->to('/projects/' . $id)
                ->with('error', 'Proyek sudah ditandai selesai.');
        }

        $this->projectModel->update($id, [
            'status'       => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/dashboard?tab=completed')
            ->with('success', 'Proyek berhasil ditandai selesai.');
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
