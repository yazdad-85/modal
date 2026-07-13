<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — ModalCalc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { min-height: 100vh; background-color: #f8f9fa; }
        .auth-card { max-width: 420px; margin: 0 auto; }
        .btn-primary, .form-control { min-height: 48px; }
    </style>
</head>
<body>
    <div class="container px-3 py-4 py-md-5">
        <div class="auth-card">
            <div class="text-center mb-4">
                <h1 class="h3 fw-bold">ModalCalc</h1>
                <p class="text-muted mb-0">Kalkulator Bagi Hasil Investasi</p>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="h5 mb-4">Masuk</h2>

                    <?php if (session('error')): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= esc(session('error')) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (session('success')): ?>
                        <div class="alert alert-success" role="alert">
                            <?= esc(session('success')) ?>
                        </div>
                    <?php endif; ?>

                    <?php helper('form'); ?>
                    <form action="<?= esc(site_url('login')) ?>" method="post" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                value="<?= esc(old('email')) ?>"
                                required
                                autocomplete="email"
                                inputmode="email"
                            >
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                required
                                autocomplete="current-password"
                            >
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Masuk</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
