# ModalCalc — Kalkulator Bagi Hasil Investasi

**Tanggal:** 2026-07-07  
**Status:** Approved  
**Stack:** CodeIgniter 4 + Bootstrap 5 + MySQL

---

## 1. Ringkasan

Aplikasi web multi-user untuk menghitung pembagian hasil investasi bisnis berdasarkan kontribusi modal. Mendukung input fleksibel (berbasis unit atau langsung), pemodal tunggal maupun multi-pemodal dengan pembagian proporsional, serta export laporan PDF/Excel untuk dibagikan ke pemodal.

### Keputusan Desain Utama

| Aspek | Keputusan |
|-------|-----------|
| Arsitektur | Monolitik CI4 server-rendered (Opsi A) |
| Input proyek | Fleksibel: unit-based ATAU total modal + total hasil jual langsung |
| Bagi hasil | % pemodal vs % operator (konfigurasi per proyek, total = 100%) |
| Multi pemodal | Proporsional menurut kontribusi modal |
| Output | Pengembalian modal + pembagian profit (breakdown terpisah) |
| User | Multi-user dengan login, simpan proyek di database |
| Akses pemodal | Owner input & kelola; pemodal terima laporan export saja |
| UI | Mobile-first, responsive Bootstrap 5 |
| Keamanan | CSRF, ownership check, input validation, security headers |

---

## 2. Logika Perhitungan

### 2.1 Input

**Mode Unit:**
- `total_modal` = jumlah_unit × harga_beli
- `total_hasil_jual` = jumlah_unit × harga_jual

**Mode Langsung:**
- User input `total_modal` dan `total_hasil_jual` secara langsung
- Field unit nullable

### 2.2 Rumus

```
keuntungan_kotor = total_hasil_jual - total_modal
```

Jika `keuntungan_kotor < 0` (rugi), tetap tampilkan breakdown dengan penanda rugi; tidak ada profit untuk dibagi.

**Pengembalian Modal (proporsional):**
```
pengembalian_pemodal[i] = total_modal × (modal_pemodal[i] / total_modal)
```

**Bagi Hasil Profit:**
```
pool_pemodal  = keuntungan_kotor × (persen_pemodal / 100)
pool_operator = keuntungan_kotor × (persen_operator / 100)

profit_pemodal[i] = pool_pemodal × (modal_pemodal[i] / total_modal)
```

**Total per Pemodal:**
```
total_pemodal[i] = pengembalian_pemodal[i] + profit_pemodal[i]
```

### 2.3 Contoh

```
Total hasil jual     = Rp 420.000.000
Total modal          = Rp 350.000.000  (A: 50jt, B: 100jt, C: 200jt)
Rasio bagi hasil     = 60% pemodal / 40% operator
─────────────────────────────────────
Keuntungan kotor     = Rp  70.000.000

Pengembalian modal:
  A (50/350)  → Rp  50.000.000
  B (100/350) → Rp 100.000.000
  C (200/350) → Rp 200.000.000

Bagi profit:
  Pool pemodal (60%) → Rp 42.000.000
    A → Rp  6.000.000
    B → Rp 12.000.000
    C → Rp 24.000.000
  Operator (40%)     → Rp 28.000.000

Total per pemodal:
  A = 50jt + 6jt  = Rp  56.000.000
  B = 100jt + 12jt = Rp 112.000.000
  C = 200jt + 24jt = Rp 224.000.000
```

### 2.4 Validasi Bisnis

- Total modal semua pemodal **harus sama** dengan total modal proyek
- Persentase pemodal + operator **harus = 100%**
- Semua nominal modal pemodal > 0
- Minimal 1 pemodal per proyek

---

## 3. Arsitektur

### 3.1 Struktur Modul

```
app/
├── Controllers/
│   ├── AuthController.php       # login, register, logout
│   ├── DashboardController.php  # daftar proyek
│   ├── ProjectController.php    # CRUD proyek + kalkulasi
│   └── ExportController.php     # PDF & Excel
├── Models/
│   ├── UserModel.php
│   ├── ProjectModel.php
│   └── InvestorModel.php
├── Libraries/
│   └── ProfitCalculator.php     # logika hitung terpusat
├── Filters/
│   └── AuthFilter.php           # proteksi route terautentikasi
└── Views/
    ├── layouts/main.php         # Bootstrap 5 layout
    ├── auth/
    ├── dashboard/
    ├── projects/
    └── exports/
```

### 3.2 Database Schema (MySQL)

**users**
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT PK AI | |
| name | VARCHAR(100) | |
| email | VARCHAR(255) UNIQUE | |
| password | VARCHAR(255) | bcrypt hash |
| created_at | DATETIME | |
| updated_at | DATETIME | |

**projects**
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT PK AI | |
| user_id | INT FK → users.id | owner |
| nama_proyek | VARCHAR(200) | |
| mode_input | ENUM('unit','direct') | |
| jumlah_unit | INT NULL | hanya mode unit |
| harga_beli | BIGINT NULL | per unit, mode unit |
| harga_jual | BIGINT NULL | per unit, mode unit |
| total_modal | BIGINT | |
| total_hasil_jual | BIGINT | |
| persen_pemodal | DECIMAL(5,2) | |
| persen_operator | DECIMAL(5,2) | |
| nama_operator | VARCHAR(100) | |
| catatan | TEXT NULL | |
| created_at | DATETIME | |
| updated_at | DATETIME | |

**investors**
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT PK AI | |
| project_id | INT FK → projects.id ON DELETE CASCADE | |
| nama | VARCHAR(100) | |
| modal | BIGINT | |
| urutan | INT | urutan tampilan |

**Indexes:**
- `projects.user_id`
- `investors.project_id`
- `users.email`

### 3.3 Alur Utama

1. User register/login
2. Dashboard menampilkan daftar proyek milik user
3. Buat/edit proyek via form wizard 3 langkah
4. Klik **Hitung & Simpan** → `ProfitCalculator` menghitung, hasil ditampilkan
5. Export PDF/Excel dengan ownership check

---

## 4. UI/UX — Mobile-First & Responsive

### 4.1 Prinsip

- Desain dimulai dari layar 320px, scale up ke tablet & desktop
- Semua touch target minimum 44×44px
- Tidak ada aksi yang hanya bisa diakses via hover
- Input nominal menggunakan keyboard numerik (`inputmode="decimal"`)
- Format Rupiah dengan pemisah ribuan di UI

### 4.2 Halaman

| Halaman | Route (contoh) | Fungsi |
|---------|----------------|--------|
| Login | `/login` | Autentikasi |
| Register | `/register` | Pendaftaran akun |
| Dashboard | `/dashboard` | Daftar proyek (card list) |
| Buat Proyek | `/projects/create` | Form wizard |
| Edit Proyek | `/projects/{id}/edit` | Form wizard (pre-filled) |
| Hasil | `/projects/{id}` | Breakdown kalkulasi |
| Export PDF | `/projects/{id}/export/pdf` | Download PDF |
| Export Excel | `/projects/{id}/export/excel` | Download Excel |

### 4.3 Form Wizard (3 Langkah)

**Langkah 1 — Data Proyek**
- Nama proyek, nama operator
- Toggle mode: Unit ↔ Langsung
- Mode Unit: jumlah pcs, harga beli/pcs, harga jual/pcs (auto-hitung total)
- Mode Langsung: total modal, total hasil jual
- Rasio bagi hasil: % pemodal / % operator (validasi total = 100%)

**Langkah 2 — Daftar Pemodal**
- Tombol tambah/hapus pemodal dinamis
- Setiap baris: nama + nominal modal
- Progress indicator: "Modal terkumpul: X / Y" dengan warna status
- Single modal: 1 baris (100%)

**Langkah 3 — Review & Hitung**
- Ringkasan semua input
- Tombol **Hitung & Simpan**
- Navigasi: kembali ke langkah sebelumnya

### 4.4 Tampilan Hasil

Accordion sections:
1. **Ringkasan** — hasil jual, total modal, keuntungan kotor
2. **Pengembalian Modal** — per pemodal
3. **Bagi Hasil Profit** — pool pemodal + operator, detail per pemodal
4. **Total per Pemodal** — modal kembali + profit

Tombol export: PDF dan Excel, full-width di mobile.

### 4.5 Responsive Breakpoints

| Breakpoint | Perilaku |
|------------|----------|
| < 576px | Single column, wizard full-screen, tabel → card list |
| 576–767px | Single column, spacing lebih lega |
| 768–991px | 2 kolom: form + live preview |
| ≥ 992px | Sidebar nav + content area, tabel penuh |

### 4.6 Komponen Bootstrap 5

- Navbar sticky (collapse di mobile)
- Card untuk daftar proyek dan hasil per pemodal
- Accordion untuk breakdown hasil
- Toast untuk notifikasi sukses/error
- Progress bar untuk status modal terkumpul
- Form floating labels untuk input

---

## 5. Keamanan

### 5.1 Autentikasi

- Password hashing: `password_hash()` bcrypt via CI4
- Session: CI4 Session library, `session_regenerate_id()` setelah login
- CSRF: CI4 CSRF filter aktif di semua form POST
- Rate limit login: max 5 percobaan per 15 menit per IP+email
- Remember me: tidak diimplementasikan di v1

### 5.2 Autorisasi

- Setiap query proyek/investor WAJIB filter `user_id = session user`
- Ownership check di controller sebelum edit, delete, view, export
- Tidak ada endpoint publik untuk data proyek

### 5.3 Input Validation

| Input | Aturan |
|-------|--------|
| Email | `valid_email`, unique, max 255 |
| Password | Min 8 karakter, huruf + angka |
| Nama proyek/pemodal | alpha_numeric_space, max length |
| Nominal modal | Integer positif, max 999.999.999.999 |
| Persentase | 0–100, total pemodal+operator = 100 |
| Jumlah unit | Integer positif |

- Output di view: `esc()` untuk cegah XSS
- Query: CI4 Query Builder / Model untuk cegah SQL injection

### 5.4 HTTP Security Headers

- `Content-Security-Policy` — restrict script sources
- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Strict-Transport-Security` — production HTTPS only

### 5.5 Export

- PDF/Excel di-generate server-side
- Download via controller dengan ownership check
- Nama file di-sanitize: `laporan-{slug-proyek}-{timestamp}.pdf`

### 5.6 Environment

- `.env` untuk credentials — tidak di-commit
- `CI_ENVIRONMENT = production` di server live
- Error display off di production
- Folder `writable/` permission 755, tidak web-accessible

---

## 6. Testing & Error Handling

**Keputusan:** Versi lengkap — unit test + feature test (bukan opsi minimal).

### 6.1 Unit Tests

`ProfitCalculator` library:
- Single pemodal, profit positif
- Multi pemodal proporsional
- Profit nol
- Profit negatif (rugi)
- Mode unit vs mode langsung
- Validasi total modal pemodal ≠ total proyek

### 6.2 Feature Tests

- Register, login, logout flow
- CRUD proyek dengan ownership
- Akses proyek milik user lain → 404
- Export dengan ownership check
- CSRF rejection pada form tanpa token

### 6.3 Pesan Error (Bahasa Indonesia)

- "Total modal pemodal tidak sama dengan total modal proyek"
- "Persentase pemodal dan operator harus berjumlah 100%"
- "Email atau password salah"
- "Terlalu banyak percobaan login. Coba lagi dalam 15 menit."

---

## 7. Dependensi

| Package | Fungsi |
|---------|--------|
| codeigniter4/framework | Framework utama |
| dompdf/dompdf | Export PDF |
| phpoffice/phpspreadsheet | Export Excel |

---

## 8. Di Luar Scope (v1)

- Undangan pemodal dengan akses read-only
- Remember me / forgot password
- Biaya tambahan (pajak, komisi, operasional)
- Multi-currency
- API publik
- Notifikasi email

---

## 9. Struktur Folder Proyek

```
modal/
├── app/                  # CI4 application
├── public/               # Web root
├── writable/             # Logs, cache, session
├── tests/                # PHPUnit tests
├── docs/
│   └── superpowers/
│       └── specs/        # Design docs
├── .env.example
├── composer.json
└── README.md
```
