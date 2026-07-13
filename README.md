# ModalCalc

Aplikasi web untuk menghitung pengembalian modal dan bagi hasil profit investasi (single & multi pemodal).

## Tech Stack

- CodeIgniter 4
- Bootstrap 5 (mobile-first)
- MySQL
- dompdf (PDF export)
- PhpSpreadsheet (Excel export)

## Setup

```bash
# Install dependencies
composer install

# Copy environment config
cp .env.example .env
php spark key:generate

# Buat database MySQL
mysql -u root -e "CREATE DATABASE modalcalc CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# Sesuaikan kredensial database di .env

# Jalankan migrasi
php spark migrate

# Jalankan server
php spark serve
```

Buka http://localhost:8081 — daftar akun, login, buat proyek.

## Testing

```bash
vendor/bin/phpunit
```

19 tests (unit + feature).

## Dokumentasi

- Spec: `docs/superpowers/specs/2026-07-07-modal-profit-calculator-design.md`
- Plan: `docs/superpowers/plans/2026-07-07-modal-profit-calculator.md`
