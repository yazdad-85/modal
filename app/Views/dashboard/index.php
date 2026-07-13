<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Dashboard</h1>
        <p class="text-muted mb-0">Kelola proyek investasi Anda</p>
    </div>
    <a href="<?= esc(site_url('projects/create')) ?>" class="btn btn-primary">
        + Proyek Baru
    </a>
</div>

<ul class="nav nav-pills dashboard-tabs mb-4 gap-2" role="tablist">
    <li class="nav-item" role="presentation">
        <a
            class="nav-link <?= $tab === 'active' ? 'active' : '' ?>"
            href="<?= esc(site_url('dashboard?tab=active')) ?>"
            role="tab"
        >
            Aktif <span class="badge <?= $tab === 'active' ? 'bg-light text-primary' : 'bg-secondary' ?> ms-1"><?= (int) $activeCount ?></span>
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a
            class="nav-link <?= $tab === 'completed' ? 'active' : '' ?>"
            href="<?= esc(site_url('dashboard?tab=completed')) ?>"
            role="tab"
        >
            Selesai <span class="badge <?= $tab === 'completed' ? 'bg-light text-primary' : 'bg-secondary' ?> ms-1"><?= (int) $completedCount ?></span>
        </a>
    </li>
</ul>

<?php if ($projects === []): ?>
    <div class="card shadow-sm border-0 empty-state">
        <div class="card-body">
            <?php if ($tab === 'completed'): ?>
                <h2 class="h5 mb-2">Belum ada proyek selesai</h2>
                <p class="text-muted mb-4">Proyek yang sudah ditandai selesai akan muncul di sini.</p>
                <a href="<?= esc(site_url('dashboard?tab=active')) ?>" class="btn btn-outline-primary">
                    Lihat Proyek Aktif
                </a>
            <?php else: ?>
                <h2 class="h5 mb-2">Belum ada proyek aktif</h2>
                <p class="text-muted mb-4">Buat proyek pertama untuk mulai menghitung bagi hasil investasi.</p>
                <a href="<?= esc(site_url('projects/create')) ?>" class="btn btn-primary">
                    Buat Proyek Pertama
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($projects as $project): ?>
            <?= view('dashboard/_project_card', ['project' => $project]) ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
