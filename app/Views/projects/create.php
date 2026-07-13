<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Buat Proyek<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="mb-4">
    <h1 class="h3 mb-1">Buat Proyek Baru</h1>
    <p class="text-muted mb-0">Isi data proyek melalui wizard 3 langkah</p>
</div>

<?= view('projects/_form', [
    'project'           => $project,
    'investors'         => $investors,
    'operationalCosts'  => $operationalCosts ?? [],
    'action'            => site_url('projects'),
    'isEdit'            => false,
]) ?>
<?= $this->endSection() ?>
