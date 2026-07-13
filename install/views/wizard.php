<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalasi ModalCalc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; background: linear-gradient(160deg, #f0f4ff 0%, #f8f9fa 55%, #eefaf3 100%); }
        .install-wrap { max-width: 640px; margin: 0 auto; }
        .install-brand { letter-spacing: -0.02em; }
        .step-pill { width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.875rem; }
        .step-pill.active { background: #0d6efd; color: #fff; }
        .step-pill.done { background: #198754; color: #fff; }
        .step-pill.pending { background: #e9ecef; color: #6c757d; }
        .req-ok { color: #198754; }
        .req-fail { color: #dc3545; }
        .card { border: 0; box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,.06); }
        .btn, .form-control { min-height: 48px; }
    </style>
</head>
<body>
    <div class="container px-3 py-4 py-md-5">
        <div class="install-wrap">
            <div class="text-center mb-4">
                <h1 class="h3 fw-bold install-brand">ModalCalc</h1>
                <p class="text-muted mb-0">Instalasi awal — konfigurasi database &amp; akun admin</p>
            </div>

            <div class="d-flex justify-content-center gap-3 mb-4">
                <?php foreach ([1 => 'Server', 2 => 'Database', 3 => 'Situs & Admin'] as $num => $label): ?>
                    <?php
                    $class = 'pending';
                    if ($displayStep === $num) {
                        $class = 'active';
                    } elseif ($displayStep > $num) {
                        $class = 'done';
                    }
                    ?>
                    <div class="text-center">
                        <span class="step-pill <?= install_esc($class) ?>"><?= $displayStep > $num ? '✓' : $num ?></span>
                        <div class="small text-muted mt-1"><?= install_esc($label) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($errors !== []): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?= install_esc($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body p-4">
                    <?php if ($step === 1): ?>
                        <h2 class="h5 mb-3">Langkah 1 — Persyaratan Server</h2>
                        <p class="text-muted small">Pastikan hosting memenuhi syarat sebelum melanjutkan.</p>

                        <ul class="list-unstyled mb-4">
                            <?php foreach ($requirements as $req): ?>
                                <li class="d-flex justify-content-between align-items-start gap-3 py-2 border-bottom">
                                    <div>
                                        <span class="<?= $req['ok'] ? 'req-ok' : 'req-fail' ?>">
                                            <?= $req['ok'] ? '✓' : '✗' ?>
                                        </span>
                                        <?= install_esc($req['label']) ?>
                                        <div class="small text-muted"><?= install_esc($req['hint']) ?></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <?php if ($requirementsOk): ?>
                            <a href="install.php?step=2" class="btn btn-primary w-100">Lanjut ke Database</a>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary w-100" disabled>Perbaiki persyaratan di atas</button>
                        <?php endif; ?>

                    <?php elseif ($displayStep === 2): ?>
                        <h2 class="h5 mb-3">Langkah 2 — Konfigurasi Database</h2>
                        <p class="text-muted small mb-4">
                            Masukkan kredensial MySQL dari cPanel. Database kosong akan dibuat otomatis jika belum ada.
                        </p>

                        <form method="post" action="install.php?step=2" novalidate>
                            <input type="hidden" name="_token" value="<?= install_esc($_SESSION['install_token']) ?>">
                            <input type="hidden" name="action" value="database">

                            <div class="mb-3">
                                <label class="form-label" for="hostname">Host</label>
                                <input type="text" class="form-control" id="hostname" name="hostname" value="<?= install_esc($dbSession['hostname']) ?>" required>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-8">
                                    <label class="form-label" for="database">Nama Database</label>
                                    <input type="text" class="form-control" id="database" name="database" value="<?= install_esc($dbSession['database']) ?>" required>
                                </div>
                                <div class="col-4">
                                    <label class="form-label" for="port">Port</label>
                                    <input type="text" class="form-control" id="port" name="port" value="<?= install_esc($dbSession['port']) ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="username">Username Database</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?= install_esc($dbSession['username']) ?>" required autocomplete="off">
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="password">Password Database</label>
                                <input type="password" class="form-control" id="password" name="password" value="<?= install_esc($dbSession['password']) ?>" autocomplete="new-password">
                            </div>

                            <div class="d-flex gap-2">
                                <a href="install.php?step=1" class="btn btn-outline-secondary flex-grow-1">Kembali</a>
                                <button type="submit" class="btn btn-primary flex-grow-1">Tes &amp; Lanjut</button>
                            </div>
                        </form>

                    <?php else: ?>
                        <h2 class="h5 mb-3">Langkah 3 — Situs &amp; Akun Admin</h2>
                        <p class="text-muted small mb-4">
                            <strong>encryption.key</strong> akan dibuat otomatis. File <code>.env</code> ditulis oleh installer.
                        </p>

                        <form method="post" action="install.php?step=3" novalidate>
                            <input type="hidden" name="_token" value="<?= install_esc($_SESSION['install_token']) ?>">
                            <input type="hidden" name="action" value="install">

                            <div class="mb-3">
                                <label class="form-label" for="baseURL">URL Situs</label>
                                <input type="url" class="form-control" id="baseURL" name="baseURL" value="<?= install_esc($detectedUrl) ?>" required>
                                <div class="form-text">Harus diakhiri slash (/). Contoh: https://domain-anda.com/</div>
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="force_https" name="force_https" <?= $https ? 'checked' : '' ?>>
                                <label class="form-check-label" for="force_https">Paksa HTTPS (disarankan di produksi)</label>
                            </div>

                            <hr class="my-4">

                            <h3 class="h6 mb-3">Akun Admin (untuk login)</h3>

                            <div class="mb-3">
                                <label class="form-label" for="admin_name">Nama</label>
                                <input type="text" class="form-control" id="admin_name" name="admin_name" value="Admin" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="admin_email">Email</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" required autocomplete="off">
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="admin_password">Password</label>
                                <input type="password" class="form-control" id="admin_password" name="admin_password" minlength="8" required autocomplete="new-password">
                                <div class="form-text">Minimal 8 karakter. Pendaftaran publik dinonaktifkan.</div>
                            </div>

                            <div class="alert alert-info small">
                                Setelah instalasi selesai, file <code>install.php</code> dan folder <code>install/</code> akan dihapus otomatis.
                            </div>

                            <div class="d-flex gap-2">
                                <a href="install.php?step=2" class="btn btn-outline-secondary flex-grow-1">Kembali</a>
                                <button type="submit" class="btn btn-success flex-grow-1">Instal Sekarang</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
