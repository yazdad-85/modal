<?php
$keuntungan  = (int) $project['total_hasil_jual'] - (int) $project['total_modal'];
$isRugi      = $keuntungan < 0;
$isCompleted = ($project['status'] ?? 'active') === 'completed';
$cardClass   = $isCompleted ? 'project-card--completed' : '';
$waktuKontrak = trim((string) ($project['waktu_kontrak'] ?? ''));
?>
<div class="col-12 col-md-6 col-lg-4">
    <div class="card project-card shadow-sm border-0 h-100 <?= esc($cardClass) ?>">
        <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <h2 class="h5 card-title mb-0 flex-grow-1">
                    <a href="<?= esc(site_url('projects/' . $project['id'])) ?>">
                        <?= esc($project['nama_proyek']) ?>
                    </a>
                </h2>
                <?php if ($isCompleted): ?>
                    <span class="badge bg-secondary">Selesai</span>
                <?php else: ?>
                    <span class="badge bg-success bg-opacity-10 text-success">Aktif</span>
                <?php endif; ?>
            </div>
            <p class="text-muted small mb-3">
                Operator: <?= esc($project['nama_operator']) ?>
                <span class="badge bg-primary bg-opacity-10 text-primary ms-1">
                    <?= $project['mode_input'] === 'unit' ? 'Unit' : 'Langsung' ?>
                </span>
            </p>

            <div class="mb-3">
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Total Modal</span>
                    <span class="money text-modal"><?= esc(format_rupiah((int) $project['total_modal'])) ?></span>
                </div>
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Hasil Jual</span>
                    <span class="money"><?= esc(format_rupiah((int) $project['total_hasil_jual'])) ?></span>
                </div>
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Kontrak</span>
                    <span class="text-end"><?= esc($waktuKontrak !== '' ? $waktuKontrak : 'Belum diisi') ?></span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span><?= $isRugi ? 'Rugi' : 'Keuntungan' ?></span>
                    <span class="money <?= $isRugi ? 'text-loss' : 'text-profit' ?>">
                        <?= esc(format_rupiah(abs($keuntungan))) ?>
                    </span>
                </div>
            </div>

            <div class="mt-auto d-grid gap-2 d-sm-flex">
                <a href="<?= esc(site_url('projects/' . $project['id'])) ?>" class="btn btn-outline-primary">
                    Lihat Hasil
                </a>
                <?php if (! $isCompleted): ?>
                    <a href="<?= esc(site_url('projects/' . $project['id'] . '/edit')) ?>" class="btn btn-outline-secondary">
                        Edit
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-footer bg-transparent border-0 pt-0">
            <small class="text-muted">
                <?php if ($isCompleted && ! empty($project['completed_at'])): ?>
                    Selesai <?= esc(date('d M Y', strtotime($project['completed_at']))) ?>
                <?php elseif (! empty($project['updated_at'])): ?>
                    Diperbarui <?= esc(date('d M Y H:i', strtotime($project['updated_at']))) ?>
                <?php endif; ?>
            </small>
        </div>
    </div>
</div>
