<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Edit Proyek<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="mb-4">
    <h1 class="h3 mb-1">Edit Proyek</h1>
    <p class="text-muted mb-0">
        <?= esc($project['nama_proyek']) ?> — ubah data melalui wizard 3 langkah (Data Proyek → Pemodal → Review)
    </p>
</div>

<?= view('projects/_form', [
    'project'           => $project,
    'investors'         => $investors,
    'operationalCosts'  => $operationalCosts ?? [],
    'action'            => site_url('projects/' . $project['id']),
    'isEdit'            => true,
]) ?>
<?= $this->endSection() ?>
