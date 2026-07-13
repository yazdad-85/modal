<?php
$isUnit = ($project['mode_input'] ?? 'direct') === 'unit';
$oldMode = old('mode_input');
if ($oldMode !== null) {
    $isUnit = $oldMode === 'unit';
}
$persenPemodalVal  = (float) old('persen_pemodal', $project['persen_pemodal'] ?? 60);
$persenOperatorVal = (float) old('persen_operator', $project['persen_operator'] ?? 40);
$operationalCosts  = $operationalCosts ?? [];
?>

<?php helper('form'); ?>

<form
    id="projectForm"
    action="<?= esc($action) ?>"
    method="post"
    novalidate
>
    <?= csrf_field() ?>

    <div id="projectWizard" class="card shadow-sm border-0" data-current-step="1">
        <div class="card-header bg-white border-bottom-0 pt-3 px-3">
            <ul class="nav nav-pills nav-fill wizard-steps flex-column flex-sm-row gap-2" role="tablist">
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link active" data-step="1" role="tab">
                        1. Data Proyek
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link" data-step="2" role="tab">
                        2. Pemodal
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link" data-step="3" role="tab">
                        3. Review
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-3 p-md-4">
            <!-- Step 1 -->
            <div class="wizard-pane active" data-step="1" role="tabpanel">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="form-floating">
                            <input
                                type="text"
                                class="form-control"
                                id="nama_proyek"
                                name="nama_proyek"
                                value="<?= esc(old('nama_proyek', $project['nama_proyek'] ?? '')) ?>"
                                placeholder="Nama proyek"
                                required
                                maxlength="200"
                            >
                            <label for="nama_proyek">Nama Proyek</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-floating">
                            <input
                                type="text"
                                class="form-control"
                                id="nama_operator"
                                name="nama_operator"
                                value="<?= esc(old('nama_operator', $project['nama_operator'] ?? '')) ?>"
                                placeholder="Nama operator"
                                required
                                maxlength="100"
                            >
                            <label for="nama_operator">Nama Operator</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label d-block">Mode Input</label>
                        <div class="btn-group w-100" role="group" aria-label="Mode input">
                            <input
                                type="radio"
                                class="btn-check"
                                name="mode_input"
                                id="mode_unit"
                                value="unit"
                                <?= $isUnit ? 'checked' : '' ?>
                                autocomplete="off"
                            >
                            <label class="btn btn-outline-primary" for="mode_unit">Per Unit</label>

                            <input
                                type="radio"
                                class="btn-check"
                                name="mode_input"
                                id="mode_direct"
                                value="direct"
                                <?= ! $isUnit ? 'checked' : '' ?>
                                autocomplete="off"
                            >
                            <label class="btn btn-outline-primary" for="mode_direct">Langsung</label>
                        </div>
                    </div>

                    <div class="col-12 mode-fields <?= $isUnit ? 'active' : '' ?>" data-mode="unit">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <div class="form-floating">
                                    <input
                                        type="text"
                                        class="form-control number-input"
                                        id="jumlah_unit"
                                        name="jumlah_unit"
                                        value="<?= esc(old('jumlah_unit', $project['jumlah_unit'] ?? '')) ?>"
                                        placeholder="Jumlah unit"
                                        inputmode="numeric"
                                        data-raw-value="<?= esc(old('jumlah_unit', $project['jumlah_unit'] ?? '')) ?>"
                                    >
                                    <label for="jumlah_unit">Jumlah Unit (pcs)</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-floating">
                                    <input
                                        type="text"
                                        class="form-control number-input"
                                        id="harga_beli"
                                        name="harga_beli"
                                        value="<?= esc(old('harga_beli', $project['harga_beli'] ?? '')) ?>"
                                        placeholder="Harga beli"
                                        inputmode="decimal"
                                        data-raw-value="<?= esc(old('harga_beli', $project['harga_beli'] ?? '')) ?>"
                                    >
                                    <label for="harga_beli">Harga Beli / pcs</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-floating">
                                    <input
                                        type="text"
                                        class="form-control number-input"
                                        id="harga_jual"
                                        name="harga_jual"
                                        value="<?= esc(old('harga_jual', $project['harga_jual'] ?? '')) ?>"
                                        placeholder="Harga jual"
                                        inputmode="decimal"
                                        data-raw-value="<?= esc(old('harga_jual', $project['harga_jual'] ?? '')) ?>"
                                    >
                                    <label for="harga_jual">Harga Jual / pcs</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-light border mb-0">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>Total Modal (otomatis)</span>
                                        <strong id="unitTotalModal" class="money text-modal">Rp 0</strong>
                                    </div>
                                    <div class="d-flex justify-content-between small">
                                        <span>Total Hasil Jual (otomatis)</span>
                                        <strong id="unitTotalJual" class="money">Rp 0</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mode-fields <?= ! $isUnit ? 'active' : '' ?>" data-mode="direct">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <input
                                        type="text"
                                        class="form-control number-input"
                                        id="total_modal"
                                        name="total_modal"
                                        value="<?= esc(old('total_modal', $project['total_modal'] ?? '')) ?>"
                                        placeholder="Total modal"
                                        inputmode="decimal"
                                        data-raw-value="<?= esc(old('total_modal', $project['total_modal'] ?? '')) ?>"
                                    >
                                    <label for="total_modal">Total Modal</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <input
                                        type="text"
                                        class="form-control number-input"
                                        id="total_hasil_jual"
                                        name="total_hasil_jual"
                                        value="<?= esc(old('total_hasil_jual', $project['total_hasil_jual'] ?? '')) ?>"
                                        placeholder="Total hasil jual"
                                        inputmode="decimal"
                                        data-raw-value="<?= esc(old('total_hasil_jual', $project['total_hasil_jual'] ?? '')) ?>"
                                    >
                                    <label for="total_hasil_jual">Total Hasil Jual</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="form-floating">
                            <input
                                type="text"
                                class="form-control decimal-input"
                                id="persen_pemodal"
                                name="persen_pemodal"
                                value="<?= esc($persenPemodalVal) ?>"
                                placeholder="Persentase pemodal"
                                required
                                inputmode="decimal"
                                data-raw-value="<?= esc($persenPemodalVal) ?>"
                            >
                            <label for="persen_pemodal">Persentase Pemodal (%)</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-floating">
                            <input
                                type="text"
                                class="form-control decimal-input"
                                id="persen_operator"
                                name="persen_operator"
                                value="<?= esc($persenOperatorVal) ?>"
                                placeholder="Persentase operator"
                                required
                                inputmode="decimal"
                                data-raw-value="<?= esc($persenOperatorVal) ?>"
                            >
                            <label for="persen_operator">Persentase Operator (%)</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded-3 p-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold">Biaya Operasional</span>
                                <span id="opsTotalBadge" class="badge bg-secondary money">Rp 0</span>
                            </div>
                            <p class="small text-muted mb-3">
                                Dipotong dari profit kotor sebelum bagi hasil. Bisa lebih dari satu item.
                            </p>

                            <div id="opsRows">
                                <?php
                                $oldOpsLabels  = old('ops_keterangan');
                                $oldOpsAmounts = old('ops_jumlah');
                                $opsRows       = [];

                                if (is_array($oldOpsLabels)) {
                                    foreach ($oldOpsLabels as $i => $label) {
                                        $opsRows[] = [
                                            'keterangan' => $label,
                                            'jumlah'     => $oldOpsAmounts[$i] ?? '',
                                        ];
                                    }
                                } else {
                                    $opsRows = $operationalCosts;
                                }
                                ?>
                                <?php foreach ($opsRows as $row): ?>
                                    <div class="ops-row row g-2 align-items-end mb-3">
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label">Keterangan</label>
                                            <input
                                                type="text"
                                                class="form-control ops-label-input"
                                                name="ops_keterangan[]"
                                                value="<?= esc($row['keterangan'] ?? '') ?>"
                                                maxlength="200"
                                                placeholder="Mis. Transport, Gaji karyawan"
                                            >
                                        </div>
                                        <div class="col-10 col-sm-5">
                                            <label class="form-label">Jumlah</label>
                                            <input
                                                type="text"
                                                class="form-control ops-amount-input number-input"
                                                name="ops_jumlah[]"
                                                value="<?= esc($row['jumlah'] ?? '') ?>"
                                                inputmode="decimal"
                                                data-raw-value="<?= esc($row['jumlah'] ?? '') ?>"
                                                placeholder="0"
                                            >
                                        </div>
                                        <div class="col-2 col-sm-1">
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger btn-remove-ops w-100"
                                                aria-label="Hapus biaya operasional"
                                            >&times;</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <button type="button" id="btnAddOps" class="btn btn-outline-primary btn-sm">
                                + Tambah Biaya Operasional
                            </button>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-floating">
                            <textarea
                                class="form-control"
                                id="catatan"
                                name="catatan"
                                placeholder="Catatan"
                                style="min-height: 100px"
                            ><?= esc(old('catatan', $project['catatan'] ?? '')) ?></textarea>
                            <label for="catatan">Catatan (opsional)</label>
                        </div>
                    </div>
                </div>

                <div class="wizard-nav d-flex justify-content-end mt-4">
                    <button type="button" class="btn btn-primary" data-wizard-next>
                        Lanjut ke Pemodal &rarr;
                    </button>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="wizard-pane" data-step="2" role="tabpanel">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold">Modal Terkumpul</span>
                        <span id="modalProgressPercent" class="badge bg-secondary">0%</span>
                    </div>
                    <div class="progress mb-2" style="height: 12px;" role="progressbar" aria-label="Progress modal">
                        <div id="modalProgressBar" class="progress-bar bg-warning" style="width: 0%"></div>
                    </div>
                    <p id="modalProgressText" class="small text-muted mb-0 money">Rp 0 / Rp 0</p>
                </div>

                <div id="investorRows">
                    <?php
                    $oldNames   = old('investor_nama');
                    $oldAmounts = old('investor_modal');
                    $rows       = [];

                    if (is_array($oldNames)) {
                        foreach ($oldNames as $i => $name) {
                            $rows[] = [
                                'nama'  => $name,
                                'modal' => $oldAmounts[$i] ?? '',
                            ];
                        }
                    } else {
                        $rows = $investors;
                    }
                    ?>
                    <?php foreach ($rows as $row): ?>
                        <div class="investor-row row g-2 align-items-end mb-3">
                            <div class="col-12 col-sm-5">
                                <label class="form-label">Nama Pemodal</label>
                                <input
                                    type="text"
                                    class="form-control investor-name-input"
                                    name="investor_nama[]"
                                    value="<?= esc($row['nama'] ?? '') ?>"
                                    maxlength="100"
                                    required
                                >
                            </div>
                            <div class="col-10 col-sm-6">
                                <label class="form-label">Modal</label>
                                <input
                                    type="text"
                                    class="form-control investor-modal-input number-input"
                                    name="investor_modal[]"
                                    value="<?= esc($row['modal'] ?? '') ?>"
                                    inputmode="decimal"
                                    data-raw-value="<?= esc($row['modal'] ?? '') ?>"
                                    required
                                >
                            </div>
                            <div class="col-2 col-sm-1">
                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-remove-investor w-100"
                                    aria-label="Hapus pemodal"
                                >&times;</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" id="btnAddInvestor" class="btn btn-outline-primary mb-4">
                    + Tambah Pemodal
                </button>

                <div class="wizard-nav d-flex justify-content-between mt-2">
                    <button type="button" class="btn btn-outline-secondary" data-wizard-prev>
                        &larr; Kembali
                    </button>
                    <button type="button" class="btn btn-primary" data-wizard-next>
                        Lanjut ke Review &rarr;
                    </button>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="wizard-pane" data-step="3" role="tabpanel">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Ringkasan &amp; Simulasi</h2>
                        <p class="text-muted small mb-0">Periksa perhitungan sebelum menyimpan proyek.</p>
                    </div>
                </div>
                <div id="reviewSummary" class="review-dashboard mb-4"></div>

                <div class="wizard-nav d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-wizard-prev>
                        &larr; Kembali
                    </button>
                    <button type="submit" class="btn btn-success">
                        <?= $isEdit ? 'Simpan Perubahan' : 'Hitung & Simpan' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
