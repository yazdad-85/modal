<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($this->renderSection('title') ?: 'ModalCalc') ?> — ModalCalc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?= esc(base_url('assets/css/app.css?v=' . filemtime(FCPATH . 'assets/css/app.css'))) ?>" rel="stylesheet">
    <?= $this->renderSection('styles') ?>
</head>
<body class="bg-light">
    <?php
    helper(['url', 'rupiah']);
    $navPath           = uri_string();
    $navDashboardActive = $navPath === 'dashboard' || str_starts_with($navPath, 'dashboard');
    $navCreateActive    = $navPath === 'projects/create';
    $userName           = session('user_name');
    $userInitials       = $userName ? name_initials($userName) : '?';
    ?>
    <nav class="navbar navbar-expand-lg app-navbar sticky-top">
        <div class="container">
            <a class="navbar-brand app-brand" href="<?= esc(site_url('dashboard')) ?>">
                <span class="app-brand-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 3L3 8v8l9 5 9-5V8l-9-5z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                        <path d="M12 12l9-5M12 12L3 7M12 12v9" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="app-brand-text">
                    <span class="app-brand-name">ModalCalc</span>
                    <span class="app-brand-tagline d-none d-sm-inline">Bagi Hasil Investasi</span>
                </span>
            </a>

            <button
                class="navbar-toggler app-navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNav"
                aria-controls="mainNav"
                aria-expanded="false"
                aria-label="Buka navigasi"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav app-nav-links me-auto mb-3 mb-lg-0">
                    <li class="nav-item">
                        <a
                            class="nav-link app-nav-link <?= $navDashboardActive ? 'active' : '' ?>"
                            href="<?= esc(site_url('dashboard')) ?>"
                            <?= $navDashboardActive ? 'aria-current="page"' : '' ?>
                        >
                            <svg class="app-nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 10.5 12 4l8 6.5V19a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-8.5z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                </ul>

                <div class="app-nav-actions d-flex flex-column flex-lg-row align-items-lg-center gap-2 gap-lg-3">
                    <a
                        href="<?= esc(site_url('projects/create')) ?>"
                        class="btn app-btn-cta <?= $navCreateActive ? 'active' : '' ?>"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Proyek Baru
                    </a>

                    <?php if ($userName): ?>
                        <div class="dropdown app-user-dropdown w-100 w-lg-auto">
                            <button
                                class="app-user-btn dropdown-toggle"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                <span class="app-user-avatar"><?= esc($userInitials) ?></span>
                                <span class="app-user-meta">
                                    <span class="app-user-label">Masuk sebagai</span>
                                    <span class="app-user-name"><?= esc($userName) ?></span>
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end app-user-menu shadow-lg">
                                <li class="dropdown-header d-lg-none"><?= esc($userName) ?></li>
                                <li>
                                    <a class="dropdown-item app-dropdown-item" href="<?= esc(site_url('dashboard')) ?>">
                                        Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item app-dropdown-item" href="<?= esc(site_url('projects/create')) ?>">
                                        Buat Proyek
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item app-dropdown-item text-danger" href="<?= esc(site_url('logout')) ?>">
                                        Keluar
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a class="nav-link app-nav-link" href="<?= esc(site_url('logout')) ?>">Keluar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-3 py-md-4">
        <?= $this->renderSection('content') ?>
    </main>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?= esc(base_url('assets/js/app.js?v=' . filemtime(FCPATH . 'assets/js/app.js'))) ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const messages = [];

            <?php if (session('success')): ?>
                messages.push({ type: 'success', text: <?= json_encode(session('success'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?> });
            <?php endif; ?>

            <?php if (session('error')): ?>
                messages.push({ type: 'danger', text: <?= json_encode(session('error'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?> });
            <?php endif; ?>

            <?php if (session('errors')): ?>
                <?php foreach ((array) session('errors') as $fieldError): ?>
                    messages.push({ type: 'danger', text: <?= json_encode($fieldError, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?> });
                <?php endforeach; ?>
            <?php endif; ?>

            if (typeof window.showToasts === 'function') {
                window.showToasts(messages);
            }
        });
    </script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
