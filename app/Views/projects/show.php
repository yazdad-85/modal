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
$totalModal          = (int) $result['total_modal'];
$totalBiayaOps       = (int) $result['total_biaya_operasional'];
$keuntunganKotor     = (int) $result['keuntungan_kotor'];
$keuntunganBersih    = (int) $result['keuntungan_bersih'];
$kotorKpiClass       = $isRugi ? 'review-kpi--loss' : 'review-kpi--profit';
$kotorLabel          = $isRugi ? 'Rugi' : 'Profit Kotor';
$bersihKpiClass      = $keuntunganBersih >= 0 ? 'review-kpi--profit' : 'review-kpi--loss';
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
                <?php if (! $isCompleted): ?>
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

    <div class="result-footer d-flex flex-column flex-sm-row gap-2 justify-content-between mt-4 pt-3 border-top">
        <a href="<?= esc(site_url('dashboard')) ?>" class="btn btn-outline-secondary">
            &larr; Kembali ke Dashboard
        </a>
        <div class="d-flex flex-column flex-sm-row gap-2">
            <?php if (! $isCompleted): ?>
                <button
                    type="button"
                    class="btn btn-success"
                    data-bs-toggle="modal"
                    data-bs-target="#completeProjectModal"
                >
                    Tandai Selesai
                </button>
            <?php endif; ?>
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

<?php if (! $isCompleted): ?>
<div class="modal fade" id="completeProjectModal" tabindex="-1" aria-labelledby="completeProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content complete-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h2 class="modal-title h5 mb-1" id="completeProjectModalLabel">Konfirmasi Dana Sudah Ditransfer</h2>
                    <p class="text-muted small mb-0">Proyek: <strong class="text-dark"><?= esc($project['nama_proyek']) ?></strong></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-3">
                <p class="mb-3">
                    Tandai selesai hanya jika <strong>semua dana di bawah</strong> sudah Anda transfer ke pemodal dan operator sesuai perhitungan.
                </p>

                <?php if ($isRugi): ?>
                    <div class="alert alert-warning small mb-0">
                        Proyek ini rugi. Pastikan pengembalian modal (jika ada) sudah disepakati dengan pemodal.
                    </div>
                <?php elseif (! $canSplitProfit): ?>
                    <div class="alert alert-warning small mb-0">
                        Biaya operasional melebihi profit kotor. Hanya pengembalian modal yang perlu ditransfer.
                    </div>
                <?php else: ?>
                    <div class="complete-modal-section">
                        <div class="complete-modal-section__title">
                            <span class="complete-modal-section__icon bg-primary bg-opacity-10 text-primary">↩</span>
                            Pengembalian Modal
                        </div>
                        <p class="complete-modal-section__hint">Dana pokok yang dikembalikan ke masing-masing pemodal.</p>
                        <?php foreach ($result['investors'] as $investor): ?>
                            <div class="complete-modal-row">
                                <span><?= esc($investor['nama']) ?></span>
                                <span class="money fw-semibold text-modal"><?= esc(format_rupiah((int) $investor['pengembalian_modal'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="complete-modal-row complete-modal-row--subtotal">
                            <span>Total pengembalian modal</span>
                            <span class="money fw-bold text-modal"><?= esc(format_rupiah($totalModal)) ?></span>
                        </div>
                    </div>

                    <div class="complete-modal-section">
                        <div class="complete-modal-section__title">
                            <span class="complete-modal-section__icon bg-success bg-opacity-10 text-success">%</span>
                            Profit Pemodal (<?= esc($persenPemodal) ?>%)
                        </div>
                        <p class="complete-modal-section__hint">Bagian keuntungan yang ditransfer ke pemodal.</p>
                        <?php foreach ($result['investors'] as $investor): ?>
                            <div class="complete-modal-row">
                                <span><?= esc($investor['nama']) ?></span>
                                <span class="money fw-semibold text-profit"><?= esc(format_rupiah((int) $investor['profit'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="complete-modal-row complete-modal-row--subtotal">
                            <span>Total profit pemodal</span>
                            <span class="money fw-bold text-profit"><?= esc(format_rupiah((int) $result['pool_pemodal'])) ?></span>
                        </div>
                    </div>

                    <div class="complete-modal-section">
                        <div class="complete-modal-section__title">
                            <span class="complete-modal-section__icon bg-primary bg-opacity-10 text-primary">Op</span>
                            Profit Operator (<?= esc($persenOperator) ?>%)
                        </div>
                        <p class="complete-modal-section__hint">Bagian keuntungan yang ditransfer ke operator.</p>
                        <div class="complete-modal-row">
                            <span><?= esc($project['nama_operator']) ?></span>
                            <span class="money fw-semibold text-modal"><?= esc(format_rupiah((int) $result['pool_operator'])) ?></span>
                        </div>
                    </div>

                    <div class="complete-modal-total">
                        <div class="complete-modal-row">
                            <span class="fw-semibold">Total yang harus sudah ditransfer</span>
                            <span class="money fw-bold">
                                <?php
                                $totalTransfer = $totalModal
                                    + (int) $result['pool_pemodal']
                                    + (int) $result['pool_operator'];
                                echo esc(format_rupiah($totalTransfer));
                                ?>
                            </span>
                        </div>
                        <p class="small text-muted mb-0 mt-2">
                            = Pengembalian modal + Profit pemodal + Profit operator
                        </p>
                    </div>
                <?php endif; ?>

                <p class="small text-muted mt-3 mb-0">
                    Setelah ditandai selesai, proyek tidak dapat diedit lagi dan dipindah ke tab <strong>Selesai</strong>.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0 flex-column flex-sm-row gap-2">
                <button type="button" class="btn btn-outline-secondary order-2 order-sm-1" data-bs-dismiss="modal">Batal</button>
                <form action="<?= esc(site_url('projects/' . $project['id'] . '/complete')) ?>" method="post" class="order-1 order-sm-2 flex-grow-1 flex-sm-grow-0">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-success w-100">Ya, Semua Dana Sudah Ditransfer</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
