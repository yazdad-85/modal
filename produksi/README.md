# ModalCalc — Panduan Instalasi & Penggunaan

**ModalCalc** adalah aplikasi web untuk menghitung **pengembalian modal** dan **bagi hasil profit** investasi bisnis. Mendukung satu atau banyak pemodal, export PDF/Excel, dan pelacakan proyek aktif/selesai.

## Fitur Utama

Selain kalkulator bagi hasil, ModalCalc mendukung **pencatatan transaksi keuangan** di samping rencana perhitungan:

- **Setor modal** — catat setoran bertahap dari setiap pemodal saat dana masuk di lapangan.
- **Pengembalian modal** dan **profit pemodal** dicatat sebagai jenis transaksi terpisah, juga boleh bertahap.
- Halaman **detail proyek** menampilkan **progres %** dan **sisa kewajiban** per pemodal untuk ketiga jenis transaksi tersebut.
- Proyek **selesai otomatis** ketika semua kewajiban ke setiap pemodal sudah lunas — tidak ada lagi tombol **Tandai Selesai** manual.

Profit operator tetap dihitung seperti biasa, tetapi tidak dicatat sebagai transaksi.

---

## Daftar Isi

1. [Persyaratan Server](#1-persyaratan-server)
2. [Instalasi di Hosting](#2-instalasi-di-hosting)
3. [Masuk ke Aplikasi](#3-masuk-ke-aplikasi)
4. [Dashboard](#4-dashboard)
5. [Membuat Proyek Baru](#5-membuat-proyek-baru)
6. [Melihat Hasil Perhitungan](#6-melihat-hasil-perhitungan)
7. [Export PDF & Excel](#7-export-pdf--excel)
8. [Transaksi & Penyelesaian Proyek](#8-transaksi--penyelesaian-proyek)
9. [Mengedit & Menghapus Proyek](#9-mengedit--menghapus-proyek)
10. [Pemecahan Masalah](#10-pemecahan-masalah)

---

## 1. Persyaratan Server

| Item | Minimum |
|------|---------|
| PHP | 8.2 atau lebih baru |
| Database | MySQL / MariaDB |
| Ekstensi PHP | mysqli, mbstring, json, openssl |
| Web server | Apache dengan **mod_rewrite** aktif |
| Permission | Folder `writable/` dapat ditulis |

---

## 2. Instalasi di Hosting

### Langkah 1 — Upload file

1. Extract file `modalcalc.zip` (jika Anda menerima arsip ZIP).
2. Upload **semua isi folder** ke **document root** hosting Anda (`public_html` atau folder domain).
3. **Tidak perlu** mengarahkan document root ke subfolder `public/` — sudah diatur otomatis oleh `.htaccess`.

Struktur setelah upload:

```
public_html/
├── .htaccess
├── index.php
├── app/
├── public/
├── install/
├── vendor/
├── writable/
└── README.md  (file ini)
```

> **Penting:** Jangan upload file `.env` dari komputer lain. Installer akan membuatnya otomatis.

### Langkah 2 — Buka URL situs

1. Buka alamat domain Anda di browser, misalnya `https://domain-anda.com/`.
2. Anda akan diarahkan ke **halaman instalasi** (`install.php`).

### Langkah 3 — Ikuti wizard instalasi

| Langkah | Yang perlu diisi |
|---------|------------------|
| **1. Server** | Cek otomatis — pastikan semua hijau (✓) |
| **2. Database** | Host, nama database, username, password MySQL dari cPanel |
| **3. Situs & Admin** | URL situs, akun admin (nama, email, password) |

Klik **Instal Sekarang**. Installer akan:

- Membuat file `.env` (termasuk `encryption.key` otomatis)
- Membuat tabel database
- Membuat akun admin
- Menghapus `install.php` dan folder `install/`
- Mengarahkan ke halaman login

### Langkah 4 — Login

Masuk dengan email dan password admin yang Anda buat di langkah 3.

### Catatan instalasi

- **Buat database kosong** di cPanel/phpMyAdmin sebelum instalasi jika hosting tidak mengizinkan pembuatan database otomatis.
- Folder `writable/` biasanya perlu permission **755** atau **775**.
- Jika aplikasi di **subfolder** (mis. `domain.com/modal/`), buka file `.htaccess` di root dan uncomment baris `RewriteBase /modal/`.
- Setelah instalasi, **pendaftaran akun publik dinonaktifkan** — hanya admin yang bisa membuat user tambahan (via terminal/SSH: `php spark user:create`).

---

## 3. Masuk ke Aplikasi

1. Buka URL situs Anda.
2. Masukkan **email** dan **password**.
3. Klik **Masuk**.

Jika lupa password, hubungi administrator server untuk reset manual di database atau buat user baru via terminal.

---

## 4. Dashboard

Setelah login, Anda melihat **Dashboard** dengan dua tab:

| Tab | Keterangan |
|-----|------------|
| **Aktif** | Proyek yang masih berjalan — bisa diedit |
| **Selesai** | Proyek yang semua kewajiban pemodalnya sudah lunas — **hanya baca**, tidak bisa diedit |

Setiap kartu proyek menampilkan nama, operator, total modal, dan status. Klik kartu untuk melihat detail perhitungan.

Tombol **Proyek Baru** di navbar untuk membuat perhitungan baru.

---

## 5. Membuat Proyek Baru

Wizard terdiri dari **3 langkah**:

### Langkah 1 — Data Proyek

| Field | Keterangan |
|-------|------------|
| Nama proyek | Nama bisnis/investasi |
| Nama operator | Pihak yang mengoperasikan (bukan pemodal) |
| Mode input | **Unit** atau **Langsung** |
| % Pemodal / % Operator | Pembagian profit, total harus **100%** |

**Mode Unit** — isi jumlah unit, harga beli per unit, harga jual per unit. Total modal dan hasil jual dihitung otomatis.

**Mode Langsung** — isi total modal dan total hasil jual secara langsung.

### Langkah 2 — Pemodal

- Tambah satu atau lebih pemodal dengan **nama** dan **nominal modal**.
- Total modal semua pemodal **harus sama** dengan total modal proyek.
- Gunakan tombol **+ Tambah Pemodal** untuk menambah baris.

### Langkah 3 — Review

- Periksa ringkasan KPI: total modal, hasil jual, keuntungan/rugi.
- Lihat breakdown **pengembalian modal**, **profit pemodal**, dan **profit operator** per orang.
- Klik **Simpan Proyek** jika sudah benar.

---

## 6. Melihat Hasil Perhitungan

Halaman detail proyek menampilkan:

- **Kartu KPI** — modal, hasil jual, keuntungan kotor, rugi (jika ada)
- **Pool pemodal & operator** — total bagian profit masing-masing pihak
- **Tabel pemodal** — pengembalian modal, profit, dan total per orang
- **Baris operator** — profit operator

Jika proyek **rugi** (hasil jual < modal), tidak ada profit untuk dibagikan — aplikasi menampilkan peringatan rugi.

---

## 7. Export PDF & Excel

Di halaman detail proyek, gunakan tombol:

- **Export PDF** — laporan siap cetak/kirim ke pemodal
- **Export Excel** — spreadsheet untuk arsip atau edit lanjutan

Laporan berisi semua data proyek, breakdown perhitungan, **progress transaksi**, dan **riwayat transaksi** (PDF & Excel).

Isi tambahan terkait transaksi:

- Status lunas / belum lunas
- Progress setor, pengembalian modal, dan pengembalian profit (sudah / target / sisa / %)
- Progress per pemodal
- Tabel riwayat transaksi (tanggal, pemodal, jenis, jumlah, catatan)

---

## 8. Transaksi & Penyelesaian Proyek

Di halaman detail proyek, catat uang nyata lewat form **Tambah Transaksi**:

| Jenis | Kapan dicatat |
|-------|----------------|
| Setor modal | Pemodal menyerahkan modal ke proyek |
| Pengembalian modal | Modal dikembalikan ke pemodal |
| Pengembalian profit | Bagian profit pemodal dibayarkan |

Setiap catatan boleh **bertahap** — tidak harus sekaligus. Aplikasi menampilkan **progres %** dan **sisa** per pemodal.

Ketika semua kewajiban pemodal lunas (setor + pengembalian modal + profit):

- Proyek **otomatis** pindah ke tab **Selesai**
- Proyek **tidak dapat diedit** lagi
- Data tetap bisa dilihat dan di-export

Tidak ada tombol **Tandai Selesai** manual — status mengikuti catatan transaksi di lapangan.

---

## 9. Mengedit & Menghapus Proyek

| Aksi | Ketersediaan |
|------|--------------|
| Edit | Hanya proyek **Aktif** |
| Hapus | Hanya proyek **Aktif** |
| Lihat & Export | Semua proyek |

Edit membuka wizard yang sama seperti pembuatan proyek, dengan data terisi.

---

## 10. Pemecahan Masalah

### Halaman kosong / error 500

- Periksa permission folder `writable/` (755 atau 775).
- Periksa log di `writable/logs/` via File Manager hosting.
- Pastikan PHP versi 8.2+.

### Installer tidak muncul

- Pastikan file `public/install.php` ada (belum terinstal).
- Pastikan `writable/install.lock` **belum** ada (hapus jika ingin instal ulang — hati-hati, data bisa tertimpa).

### Database gagal koneksi

- Periksa host (biasanya `localhost`), nama database, username, password di cPanel.
- Buat database kosong manual di phpMyAdmin, lalu ulangi instalasi.

### URL/asset tidak load (CSS/JS hilang)

- Pastikan `app.baseURL` di `.env` sesuai URL situs (dengan `https://` dan trailing slash `/`).
- Jika di subfolder, sesuaikan `RewriteBase` di `.htaccess`.

### Link masih memuat `index.php`

- Pastikan mod_rewrite Apache aktif.
- Hubungi support hosting untuk mengaktifkan `AllowOverride All`.

### Ingin menambah user admin lain

Jika hosting menyediakan SSH/terminal:

```bash
php spark user:create "Nama User" email@domain.com password123
```

---

## Ringkasan Alur Kerja

```
Login → Dashboard → Proyek Baru → Isi data (3 langkah) → Simpan
     → Lihat hasil → Catat transaksi (setor / pengembalian) → Export PDF/Excel
     → Semua kewajiban lunas → Proyek otomatis masuk tab Selesai (arsip)
```

---

**ModalCalc** — Kalkulator Bagi Hasil Investasi
