<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($project['nama_proyek']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$isRugi              = $result['rugi'];
$canSplitProfit      = $result['profit_dapat_dibagikan'];
$isUnit              = $project['mode_input'] === 'unit';
$isCompleted         = ($project['status'] ?? 'active') === 'completed';
$modeBadge           = $isUnit ? 'Per Unit' : 'Langsung';
$persenPemodal       = (float) $project['persen_pemodal'];
$persenOperator      = (float) $project['persen_operator'];
$waktuKontrak        = trim((string) ($project['waktu_kontrak'] ?? ''));
$totalModal          = (int) $result['total_modal'];
$totalBiayaOps       = (int) $result['total_biaya_operasional'];
$keuntunganKotor     = (int) $result['keuntungan_kotor'];
$keuntunganBersih    = (int) $result['keuntungan_bersih'];
$kotorKpiClass       = $isRugi ? 'review-kpi--loss' : 'review-kpi--profit';
$kotorLabel          = $isRugi ? 'Rugi' : 'Profit Kotor';
$bersihKpiClass      = $keuntunganBersih >= 0 ? 'review-kpi--profit' : 'review-kpi--loss';

$progress            = $progress ?? ['project' => [], 'investors' => [], 'is_fully_settled' => false];
$transactions        = $transactions ?? [];
$investorNames       = $investorNames ?? [];
$hasTransactions     = ! empty($hasTransactions);
$jenisLabel          = [
    'setor_modal'          => 'Setor modal',
    'pengembalian_modal'   => 'Pengembalian modal',
    'pengembalian_profit'  => 'Pengembalian profit',
];
$projectProgress     = $progress['project'] ?? [];
$investorProgress    = $progress['investors'] ?? [];
$isFullySettled      = ! empty($progress['is_fully_settled']);
$settledCount        = 0;
foreach ($investorProgress as $row) {
    if (! empty($row['settled'])) {
        $settledCount++;
    }
}
$investorTotal       = count($investorProgress);
$today               = date('Y-m-d');
$canRecordTransactions = ! ($isCompleted && ! $hasTransactions);
$transactionProgressJson = json_encode(
    $investorProgress,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);
?>

<div class="result-page">
    <div class="result-header mb-4">
        <a href="<?= esc(site_url('dashboard')) ?>" class="result-back-link small text-muted text-decoration-none d-inline-flex align-items-center gap-1 mb-2">
            &larr; Dashboard
        </a>
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
            <div>
                <h1 class="h3 mb-1"><?= esc($project['nama_proyek']) ?></h1>
                <p class="text-muted mb-0">
                    Operator <strong class="text-dark"><?= esc($project['nama_operator']) ?></strong>
                    <span class="badge bg-primary bg-opacity-10 text-primary ms-1"><?= esc($modeBadge) ?></span>
                    <span class="d-block d-sm-inline mt-1 mt-sm-0 ms-sm-1">
                        Kontrak <strong class="text-dark"><?= esc($waktuKontrak !== '' ? $waktuKontrak : 'Belum diisi') ?></strong>
                    </span>
                    <?php if ($isCompleted): ?>
                        <span class="badge bg-secondary ms-1">Selesai</span>
                        <?php if (! empty($project['completed_at'])): ?>
                            <span class="small text-muted ms-1"><?= esc(date('d M Y', strtotime($project['completed_at']))) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge bg-success bg-opacity-10 text-success ms-1">Aktif</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="result-actions d-flex flex-wrap gap-2">
                <?php if (! $hasTransactions && ! $isCompleted): ?>
                    <a href="<?= esc(site_url('projects/' . $project['id'] . '/edit')) ?>" class="btn btn-outline-secondary">
                        Edit
                    </a>
                <?php endif; ?>
                <a href="<?= esc(site_url('projects/' . $project['id'] . '/export/pdf')) ?>" class="btn btn-outline-primary">
                    PDF
                </a>
                <a href="<?= esc(site_url('projects/' . $project['id'] . '/export/excel')) ?>" class="btn btn-outline-success">
                    Excel
                </a>
            </div>
        </div>
    </div>

    <div class="card review-section-card mb-3">
        <div class="card-body py-3">
            <div class="d-flex flex-column flex-sm-row justify-content-between gap-1">
                <span class="text-muted small">Waktu Kontrak Proyek</span>
                <strong><?= esc($waktuKontrak !== '' ? $waktuKontrak : 'Belum diisi') ?></strong>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4 review-kpi-row">
        <div class="col-6 col-md-4 col-lg">
            <div class="card review-kpi review-kpi--modal h-100">
                <div class="card-body">
                    <div class="review-kpi-label">Total Modal</div>
                    <div class="review-kpi-value money text-modal"><?= esc(format_rupiah($totalModal)) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card review-kpi review-kpi--jual h-100">
                <div class="card-body">
                    <div class="review-kpi-label">Hasil Jual</div>
                    <div class="review-kpi-value money"><?= esc(format_rupiah((int) $result['total_hasil_jual'])) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card review-kpi <?= esc($kotorKpiClass) ?> h-100">
                <div class="card-body">
                    <div class="review-kpi-label"><?= esc($kotorLabel) ?></div>
                    <div class="review-kpi-value money <?= $isRugi ? 'text-loss' : 'text-profit' ?>">
                        <?= esc(format_rupiah(abs($keuntunganKotor))) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card review-kpi review-kpi--ops h-100">
                <div class="card-body">
                    <div class="review-kpi-label">Biaya Operasional</div>
                    <div class="review-kpi-value money text-warning"><?= esc(format_rupiah($totalBiayaOps)) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card review-kpi <?= esc($bersihKpiClass) ?> h-100">
                <div class="card-body">
                    <div class="review-kpi-label">Profit Bersih</div>
                    <div class="review-kpi-value money <?= $keuntunganBersih >= 0 ? 'text-profit' : 'text-loss' ?>">
                        <?= esc(format_rupiah(abs($keuntunganBersih))) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg">
            <div class="card review-kpi review-kpi--split h-100">
                <div class="card-body">
                    <div class="review-kpi-label">Bagi Hasil</div>
                    <div class="review-kpi-value" style="font-size:0.95rem">
                        <?= esc($persenPemodal) ?>% <span class="text-muted">/</span> <?= esc($persenOperator) ?>%
                    </div>
                    <div class="review-split-bar mt-2">
                        <div class="review-split-bar__pemodal" style="width:<?= esc($persenPemodal) ?>%"></div>
                        <div class="review-split-bar__operator" style="width:<?= esc($persenOperator) ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isUnit): ?>
        <div class="card review-project-card mb-3">
            <div class="card-body">
                <div class="fw-semibold mb-3">Detail Unit</div>
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <span class="text-muted small d-block">Jumlah Unit</span>
                        <strong><?= esc(number_format((int) $project['jumlah_unit'], 0, ',', '.')) ?> pcs</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted small d-block">Harga Beli / pcs</span>
                        <strong class="money"><?= esc(format_rupiah((int) $project['harga_beli'])) ?></strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted small d-block">Harga Jual / pcs</span>
                        <strong class="money"><?= esc(format_rupiah((int) $project['harga_jual'])) ?></strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted small d-block">Margin / pcs</span>
                        <strong class="money text-profit"><?= esc(format_rupiah((int) $project['harga_jual'] - (int) $project['harga_beli'])) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card review-section-card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="section-icon bg-success bg-opacity-10 text-success">⇄</span>
                Progress Transaksi
            </div>
            <?php if ($canRecordTransactions): ?>
                <button
                    type="button"
                    class="btn btn-sm btn-success"
                    data-bs-toggle="modal"
                    data-bs-target="#recordTransactionModal"
                >
                    Catat Transaksi
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php
            $projectCards = [
                'setor'  => ['label' => 'Setor modal', 'metric' => $projectProgress['setor'] ?? null],
                'modal'  => ['label' => 'Pengembalian modal', 'metric' => $projectProgress['modal'] ?? null],
                'profit' => ['label' => 'Pengembalian profit', 'metric' => $projectProgress['profit'] ?? null],
            ];
            ?>
            <div class="row g-3 mb-4">
                <?php foreach ($projectCards as $card): ?>
                    <?php
                    $metric = $card['metric'] ?? ['target' => 0, 'sudah' => 0, 'sisa' => 0, 'persen' => 0];
                    $persen = max(0, min(100, (int) ($metric['persen'] ?? 0)));
                    ?>
                    <div class="col-12 col-md-4">
                        <div class="card tx-progress-card h-100 border">
                            <div class="card-body">
                                <div class="review-kpi-label"><?= esc($card['label']) ?></div>
                                <div class="fw-semibold money mb-1">
                                    <?= esc(format_rupiah((int) $metric['sudah'])) ?>
                                    <span class="text-muted fw-normal">/</span>
                                    <?= esc(format_rupiah((int) $metric['target'])) ?>
                                </div>
                                <div class="tx-progress-bar mb-2">
                                    <span style="width:<?= esc($persen) ?>%"></span>
                                </div>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span><?= esc($persen) ?>%</span>
                                    <span>Sisa <?= esc(format_rupiah((int) $metric['sisa'])) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($investorProgress === []): ?>
                <p class="text-muted small mb-0">Belum ada data progres pemodal.</p>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($investorProgress as $invRow): ?>
                        <?php
                        $setorPct  = max(0, min(100, (int) ($invRow['setor']['persen'] ?? 0)));
                        $modalPct  = max(0, min(100, (int) ($invRow['modal']['persen'] ?? 0)));
                        $profitPct = max(0, min(100, (int) ($invRow['profit']['persen'] ?? 0)));
                        ?>
                        <div class="col-12 col-md-6">
                            <div class="card tx-progress-card h-100 border">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                        <div>
                                            <div class="fw-semibold"><?= esc($invRow['nama']) ?></div>
                                            <?php if (! empty($invRow['settled'])): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success">Tuntas</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning">Belum tuntas</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($canRecordTransactions): ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-success"
                                                data-bs-toggle="modal"
                                                data-bs-target="#recordTransactionModal"
                                                data-investor-id="<?= esc((string) $invRow['investor_id']) ?>"
                                            >
                                                Catat
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span class="text-muted">Setor</span>
                                            <span><?= esc($setorPct) ?>%</span>
                                        </div>
                                        <div class="tx-progress-bar">
                                            <span style="width:<?= esc($setorPct) ?>%"></span>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span class="text-muted">Kembali modal</span>
                                            <span><?= esc($modalPct) ?>%</span>
                                        </div>
                                        <div class="tx-progress-bar">
                                            <span style="width:<?= esc($modalPct) ?>%"></span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span class="text-muted">Kembali profit</span>
                                            <span><?= esc($profitPct) ?>%</span>
                                        </div>
                                        <div class="tx-progress-bar">
                                            <span style="width:<?= esc($profitPct) ?>%"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card review-section-card mb-3">
        <div class="card-header">
            <span class="section-icon bg-secondary bg-opacity-10 text-secondary">☰</span>
            Riwayat Transaksi
        </div>
        <div class="card-body p-0">
            <?php if ($transactions === []): ?>
                <div class="p-3 text-muted small">Belum ada transaksi tercatat.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table review-table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Pemodal</th>
                                <th>Jenis</th>
                                <th class="text-end">Jumlah</th>
                                <th>Catatan</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $tx): ?>
                                <?php
                                $txInvestorId = (int) ($tx['investor_id'] ?? 0);
                                $txJenis      = (string) ($tx['jenis'] ?? '');
                                ?>
                                <tr>
                                    <td data-label="Tanggal">
                                        <?= esc(! empty($tx['tanggal']) ? date('d M Y', strtotime($tx['tanggal'])) : '—') ?>
                                    </td>
                                    <td data-label="Pemodal">
                                        <?= esc($investorNames[$txInvestorId] ?? 'Pemodal #' . $txInvestorId) ?>
                                    </td>
                                    <td data-label="Jenis">
                                        <?= esc($jenisLabel[$txJenis] ?? $txJenis) ?>
                                    </td>
                                    <td class="text-end money fw-semibold" data-label="Jumlah">
                                        <?= esc(format_rupiah((int) ($tx['jumlah'] ?? 0))) ?>
                                    </td>
                                    <td data-label="Catatan">
                                        <?= esc($tx['catatan'] ?? '') !== '' ? esc($tx['catatan']) : '—' ?>
                                    </td>
                                    <td class="text-end" data-label="Aksi">
                                        <form
                                            action="<?= esc(site_url('projects/' . $project['id'] . '/transactions/' . $tx['id'] . '/delete')) ?>"
                                            method="post"
                                            class="d-inline"
                                            onsubmit="return confirm('Hapus transaksi ini?');"
                                        >
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card review-section-card">
        <div class="card-header">
            <span class="section-icon bg-primary bg-opacity-10 text-primary">👥</span>
            Kontribusi Pemodal
        </div>
        <div class="card-body">
            <?php foreach ($result['investors'] as $index => $investor): ?>
                <?php $share = $totalModal > 0 ? round(($investor['modal'] / $totalModal) * 100, 1) : 0; ?>
                <div class="review-investor-chip">
                    <div>
                        <div class="chip-name"><?= esc($investor['nama']) ?></div>
                        <div class="chip-meta">Pemodal <?= $index + 1 ?> · <?= esc($share) ?>% kontribusi</div>
                    </div>
                    <div class="money fw-semibold text-modal"><?= esc(format_rupiah((int) $investor['modal'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($totalBiayaOps > 0): ?>
        <div class="card review-section-card mb-3">
            <div class="card-header">
                <span class="section-icon bg-warning bg-opacity-10 text-warning">−</span>
                Biaya Operasional
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table review-table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Keterangan</th>
                                <th class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result['biaya_operasional'] as $cost): ?>
                                <tr>
                                    <td data-label="Keterangan"><?= esc($cost['keterangan']) ?></td>
                                    <td class="text-end money text-warning fw-semibold" data-label="Jumlah">
                                        <?= esc(format_rupiah((int) $cost['jumlah'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-light">
                                <td class="fw-semibold" data-label="Total">Total Biaya Operasional</td>
                                <td class="text-end money fw-bold text-warning" data-label="Jumlah">
                                    <?= esc(format_rupiah($totalBiayaOps)) ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($isRugi): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
            <span class="fs-5">⚠️</span>
            <div>
                <strong>Proyek mengalami rugi</strong><br>
                <span class="small">Tidak ada profit yang dibagikan ke pemodal maupun operator.</span>
            </div>
        </div>
    <?php elseif (! $canSplitProfit): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
            <span class="fs-5">⚠️</span>
            <div>
                <strong>Biaya operasional melebihi profit kotor</strong><br>
                <span class="small">Tidak ada profit yang dibagikan ke pemodal maupun operator.</span>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
                <div class="card review-pool-card review-pool-card--pemodal h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="pool-avatar">P</div>
                        <div class="flex-grow-1">
                            <div class="small text-muted">Pool Pemodal · <?= esc($persenPemodal) ?>%</div>
                            <div class="h5 money text-profit mb-0"><?= esc(format_rupiah((int) $result['pool_pemodal'])) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="card review-pool-card review-pool-card--operator h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="pool-avatar"><?= esc(name_initials($project['nama_operator'])) ?></div>
                        <div class="flex-grow-1">
                            <div class="small text-muted">
                                Operator · <?= esc($project['nama_operator']) ?> · <?= esc($persenOperator) ?>%
                            </div>
                            <div class="h5 money text-modal mb-0"><?= esc(format_rupiah((int) $result['pool_operator'])) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card review-section-card">
            <div class="card-header">
                <span class="section-icon bg-success bg-opacity-10 text-success">↩</span>
                Pengembalian Modal
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table review-table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Pemodal</th>
                                <th class="text-end">Modal</th>
                                <th class="text-end">Dikembalikan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result['investors'] as $investor): ?>
                                <tr>
                                    <td data-label="Pemodal"><?= esc($investor['nama']) ?></td>
                                    <td class="text-end money" data-label="Modal"><?= esc(format_rupiah((int) $investor['modal'])) ?></td>
                                    <td class="text-end money text-modal fw-semibold" data-label="Dikembalikan">
                                        <?= esc(format_rupiah((int) $investor['pengembalian_modal'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card review-section-card">
            <div class="card-header">
                <span class="section-icon bg-success bg-opacity-10 text-success">%</span>
                Bagi Hasil Profit
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table review-table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Penerima</th>
                                <th class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result['investors'] as $investor): ?>
                                <tr>
                                    <td data-label="Penerima"><?= esc($investor['nama']) ?></td>
                                    <td class="text-end money text-profit fw-semibold" data-label="Jumlah">
                                        <?= esc(format_rupiah((int) $investor['profit'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="review-operator-row">
                                <td data-label="Penerima">
                                    <span class="badge bg-primary bg-opacity-10 text-primary me-1">Operator</span>
                                    <?= esc($project['nama_operator']) ?>
                                </td>
                                <td class="text-end money text-modal fw-semibold" data-label="Jumlah">
                                    <?= esc(format_rupiah((int) $result['pool_operator'])) ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card review-section-card">
            <div class="card-header">
                <span class="section-icon bg-primary bg-opacity-10 text-primary">∑</span>
                Total per Pemodal
            </div>
            <div class="card-body">
                <?php foreach ($result['investors'] as $investor): ?>
                    <div class="review-total-card">
                        <div class="total-header"><?= esc($investor['nama']) ?></div>
                        <div class="total-row">
                            <span class="text-muted">Pengembalian Modal</span>
                            <span class="money"><?= esc(format_rupiah((int) $investor['pengembalian_modal'])) ?></span>
                        </div>
                        <div class="total-row">
                            <span class="text-muted">Profit</span>
                            <span class="money text-profit"><?= esc(format_rupiah((int) $investor['profit'])) ?></span>
                        </div>
                        <div class="total-row total-row--grand">
                            <span>Total Diterima</span>
                            <span class="money"><?= esc(format_rupiah((int) $investor['total'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (! empty($project['catatan'])): ?>
        <div class="card review-section-card">
            <div class="card-header">
                <span class="section-icon bg-secondary bg-opacity-10 text-secondary">📝</span>
                Catatan
            </div>
            <div class="card-body">
                <div class="review-catatan"><?= esc($project['catatan']) ?></div>
            </div>
        </div>
    <?php endif; ?>

    <div class="result-footer d-flex flex-column flex-sm-row gap-2 justify-content-between align-items-sm-center mt-4 pt-3 border-top">
        <a href="<?= esc(site_url('dashboard')) ?>" class="btn btn-outline-secondary">
            &larr; Kembali ke Dashboard
        </a>
        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
            <div class="small">
                <?php if ($isFullySettled): ?>
                    <span class="badge bg-success">Selesai (otomatis)</span>
                    <?php if (! empty($project['completed_at'])): ?>
                        <span class="text-muted ms-1"><?= esc(date('d M Y', strtotime($project['completed_at']))) ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-muted">Belum lunas — <?= esc($settledCount) ?>/<?= esc($investorTotal) ?> pemodal tuntas</span>
                <?php endif; ?>
            </div>
            <form action="<?= esc(site_url('projects/' . $project['id'] . '/delete')) ?>" method="post"
                  onsubmit="return confirm('Yakin ingin menghapus proyek ini?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-danger w-100 w-sm-auto">
                    Hapus Proyek
                </button>
            </form>
        </div>
    </div>
</div>

<?php if ($canRecordTransactions): ?>
<div class="modal fade" id="recordTransactionModal" tabindex="-1" aria-labelledby="recordTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= esc(site_url('projects/' . $project['id'] . '/transactions')) ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h2 class="modal-title h5" id="recordTransactionModalLabel">Catat Transaksi</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="txInvestorId" class="form-label">Pemodal</label>
                        <select name="investor_id" id="txInvestorId" class="form-select" required>
                            <option value="">Pilih pemodal</option>
                            <?php foreach ($investorNames as $invId => $invName): ?>
                                <option value="<?= esc((string) $invId) ?>"><?= esc($invName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="txJenis" class="form-label">Jenis</label>
                        <select name="jenis" id="txJenis" class="form-select" required>
                            <?php foreach ($jenisLabel as $value => $label): ?>
                                <option value="<?= esc($value) ?>"><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="txJumlah" class="form-label">Jumlah</label>
                        <input
                            type="text"
                            name="jumlah"
                            id="txJumlah"
                            class="form-control number-input"
                            inputmode="numeric"
                            autocomplete="off"
                            placeholder="0"
                            data-raw-value="0"
                            required
                        >
                        <div class="tx-amount-help mt-2" id="txAmountHelp">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="tx-amount-help__label" id="txAmountLabel">Pilih pemodal dan jenis transaksi</div>
                                    <div class="tx-amount-help__amount money" id="txAmountRemaining">Rp 0</div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-success" id="txFillRemaining" disabled>
                                    Isi sisa
                                </button>
                            </div>
                            <div class="small text-muted mt-1" id="txAmountDetail">
                                Sisa yang harus dicatat akan muncul di sini.
                            </div>
                            <div class="tx-remaining-list mt-2" aria-label="Ringkasan sisa transaksi pemodal">
                                <div>
                                    <span>Setor</span>
                                    <strong class="money" id="txSisaSetor">Rp 0</strong>
                                </div>
                                <div>
                                    <span>Kembali modal</span>
                                    <strong class="money" id="txSisaModal">Rp 0</strong>
                                </div>
                                <div>
                                    <span>Kembali profit</span>
                                    <strong class="money" id="txSisaProfit">Rp 0</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="txTanggal" class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" id="txTanggal" class="form-control" value="<?= esc($today) ?>" required>
                    </div>
                    <div class="mb-0">
                        <label for="txCatatan" class="form-label">Catatan</label>
                        <textarea name="catatan" id="txCatatan" class="form-control" rows="2" placeholder="Opsional"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var modalEl = document.getElementById('recordTransactionModal');
    if (!modalEl) return;

    var investorProgress = <?= $transactionProgressJson !== false ? $transactionProgressJson : '[]' ?>;
    var progressByInvestor = {};
    investorProgress.forEach(function (row) {
        progressByInvestor[String(row.investor_id)] = row;
    });

    var jenisToMetric = {
        setor_modal: 'setor',
        pengembalian_modal: 'modal',
        pengembalian_profit: 'profit'
    };
    var jenisLabels = {
        setor_modal: 'Setor modal',
        pengembalian_modal: 'Pengembalian modal',
        pengembalian_profit: 'Pengembalian profit'
    };

    var investorSelect = document.getElementById('txInvestorId');
    var jenisSelect = document.getElementById('txJenis');
    var amountInput = document.getElementById('txJumlah');
    var amountLabel = document.getElementById('txAmountLabel');
    var amountRemaining = document.getElementById('txAmountRemaining');
    var amountDetail = document.getElementById('txAmountDetail');
    var fillRemainingButton = document.getElementById('txFillRemaining');
    var sisaSetor = document.getElementById('txSisaSetor');
    var sisaModal = document.getElementById('txSisaModal');
    var sisaProfit = document.getElementById('txSisaProfit');
    var currentRemaining = 0;

    function parseAmount(value) {
        var cleaned = String(value || '').replace(/[^\d]/g, '');
        return cleaned === '' ? 0 : parseInt(cleaned, 10);
    }

    function formatRupiah(amount) {
        return 'Rp ' + parseAmount(amount).toLocaleString('id-ID');
    }

    function metric(row, key) {
        return row && row[key] ? row[key] : { target: 0, sudah: 0, sisa: 0, persen: 0 };
    }

    function selectedMetric() {
        var row = progressByInvestor[String(investorSelect ? investorSelect.value : '')];
        var key = jenisToMetric[jenisSelect ? jenisSelect.value : ''];
        return {
            row: row || null,
            key: key || '',
            data: metric(row, key)
        };
    }

    function setAmountInput(raw) {
        if (!amountInput) return;
        amountInput.dataset.rawValue = String(raw);
        amountInput.value = raw > 0 ? raw.toLocaleString('id-ID') : '';
        amountInput.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function validateAmount() {
        if (!amountInput) return;
        var picked = selectedMetric();
        var raw = parseAmount(amountInput.dataset.rawValue || amountInput.value);

        amountInput.setCustomValidity('');
        if (!picked.row || !picked.key || raw === 0) {
            return;
        }
        if (raw > picked.data.sisa) {
            amountInput.setCustomValidity('Jumlah melebihi sisa ' + formatRupiah(picked.data.sisa) + '.');
        }
    }

    function updateAmountHelp() {
        var picked = selectedMetric();
        var row = picked.row;
        var data = picked.data;
        var jenis = jenisSelect ? jenisSelect.value : '';
        var label = jenisLabels[jenis] || 'Transaksi';

        if (sisaSetor) sisaSetor.textContent = formatRupiah(metric(row, 'setor').sisa);
        if (sisaModal) sisaModal.textContent = formatRupiah(metric(row, 'modal').sisa);
        if (sisaProfit) sisaProfit.textContent = formatRupiah(metric(row, 'profit').sisa);

        currentRemaining = row && picked.key ? parseAmount(data.sisa) : 0;
        if (amountInput) {
            amountInput.placeholder = currentRemaining > 0 ? currentRemaining.toLocaleString('id-ID') : '0';
        }
        if (fillRemainingButton) {
            fillRemainingButton.disabled = currentRemaining <= 0;
        }

        if (!row) {
            if (amountLabel) amountLabel.textContent = 'Pilih pemodal dan jenis transaksi';
            if (amountRemaining) amountRemaining.textContent = 'Rp 0';
            if (amountDetail) amountDetail.textContent = 'Sisa yang harus dicatat akan muncul di sini.';
            validateAmount();
            return;
        }

        if (amountLabel) amountLabel.textContent = 'Sisa ' + label.toLowerCase();
        if (amountRemaining) amountRemaining.textContent = formatRupiah(data.sisa);
        if (amountDetail) {
            amountDetail.textContent = 'Sudah ' + formatRupiah(data.sudah) + ' dari target ' + formatRupiah(data.target) + '.';
        }
        validateAmount();
    }

    modalEl.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var investorId = button && button.getAttribute('data-investor-id');
        if (investorSelect && investorId) {
            investorSelect.value = investorId;
        }
        updateAmountHelp();
    });

    if (investorSelect) investorSelect.addEventListener('change', updateAmountHelp);
    if (jenisSelect) jenisSelect.addEventListener('change', updateAmountHelp);
    if (amountInput) amountInput.addEventListener('input', validateAmount);
    if (fillRemainingButton) {
        fillRemainingButton.addEventListener('click', function () {
            setAmountInput(currentRemaining);
        });
    }

    updateAmountHelp();
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
