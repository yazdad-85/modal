# ModalCalc — Fitur Transaksi Keuangan Pemodal

**Tanggal:** 2026-07-13  
**Status:** Approved (brainstorm)  
**Stack:** CodeIgniter 4 + Bootstrap 5 + MySQL  
**Pendekatan:** Buku transaksi sederhana (Opsi A)

---

## 1. Ringkasan

Menambahkan catatan uang nyata di samping rencana perhitungan yang sudah ada. Saat ini aplikasi hanya menyimpan target (modal per pemodal + hasil kalkulasi pengembalian/profit). Fitur ini mencatat **setor modal**, **pengembalian modal**, dan **pengembalian profit** secara bertahap, menampilkan progres (% dan sisa) di halaman detail proyek, serta menandai proyek **selesai otomatis** ketika semua kewajiban pemodal lunas.

### Keputusan Desain Utama

| Aspek | Keputusan |
|-------|-----------|
| Arsitektur data | Satu tabel `transactions` (bukan ledger penuh, bukan tabel kewajiban terpisah) |
| Arah setor | Uang **masuk dari pemodal** ke proyek |
| Parsial | Setor dan pengembalian boleh bertahap |
| Jenis pengembalian | Terpisah: `pengembalian_modal` vs `pengembalian_profit` |
| Operator | Profit dihitung seperti sekarang; **tidak** dilacak sebagai transaksi |
| Rekapan | Hanya di halaman detail proyek (bukan dashboard global) |
| Status selesai | Auto-complete saat semua setor + pengembalian modal + profit pemodal lunas; tombol “Tandai Selesai” dihapus |
| Edit proyek | Diblokir jika sudah ada ≥1 transaksi (target tidak boleh bergeser) |

---

## 2. Konteks yang Ada

- Target setor = `investors.modal`
- Target pengembalian modal / profit = output `ProfitCalculator` per pemodal (`pengembalian_modal`, `profit`)
- Status proyek: `active` \| `completed`
- “Tandai Selesai” saat ini hanya flip status tanpa catatan uang

---

## 3. Model Data

### 3.1 Tabel `transactions`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | INT PK AI | |
| `project_id` | INT FK → projects | Cascade delete |
| `investor_id` | INT FK → investors | Cascade delete |
| `jenis` | ENUM/VARCHAR | `setor_modal` \| `pengembalian_modal` \| `pengembalian_profit` |
| `jumlah` | BIGINT | Nominal > 0 |
| `tanggal` | DATE | Tanggal transaksi |
| `catatan` | VARCHAR nullable | Opsional |
| `created_by` | INT FK → users | User yang mencatat |
| `created_at` / `updated_at` | DATETIME | |

Index: `(project_id)`, `(investor_id)`, `(project_id, jenis)`, `(tanggal)`.

### 3.2 Target (bukan tabel baru)

Dihitung on-the-fly per pemodal:

```
target_setor              = investors.modal
target_pengembalian_modal = result.investors[i].pengembalian_modal
target_pengembalian_profit= result.investors[i].profit   # 0 jika rugi / tidak bisa bagi
```

```
sudah_*  = SUM(transactions.jumlah) WHERE jenis = ... AND investor_id = ...
sisa_*   = max(0, target_* - sudah_*)
persen_* = target_* > 0 ? round(sudah_* / target_* × 100) : 100
```

Target profit 0 dianggap **sudah lunas** (tidak menghalangi auto-complete).

---

## 4. Aturan Bisnis

### 4.1 Kapan boleh dicatat

| Jenis | Syarat |
|-------|--------|
| `setor_modal` | Proyek milik user; setelah tersimpan |
| `pengembalian_modal` / `pengembalian_profit` | Sama; boleh dicatat meski setor belum 100% (UI memberi peringatan lembut) |

### 4.2 Validasi simpan

- `jumlah` > 0
- `jumlah` ≤ sisa target jenis tersebut untuk pemodal itu
- `investor_id` harus milik `project_id`
- Ownership proyek = session user
- CSRF

### 4.3 Hapus / ubah transaksi

- Hapus transaksi diperbolehkan selama user pemilik proyek, **termasuk** saat status `completed` (agar bisa koreksi dan reopen otomatis)
- Tidak perlu form edit: hapus lalu catat ulang
- Setelah hapus/simpan: hitung ulang auto-complete

### 4.4 Kunci edit proyek

Jika `COUNT(transactions WHERE project_id)` ≥ 1:

- Blokir `edit` / `update` angka proyek, investor, biaya operasional (sama ketatnya dengan status completed)
- Hapus proyek tetap boleh (cascade hapus transaksi)

### 4.5 Auto-complete

Ganti alur “Tandai Selesai” manual.

Proyek menjadi `completed` (+ `completed_at`) ketika **semua** pemodal memenuhi:

1. `sudah_setor >= target_setor`
2. `sudah_pengembalian_modal >= target_pengembalian_modal`
3. `sudah_pengembalian_profit >= target_pengembalian_profit` (atau target profit = 0)

Jika setelah hapus transaksi kondisi tidak lagi terpenuhi → status kembali `active`, `completed_at = null`.

Route `POST projects/(:num)/complete` dihapus atau diganti no-op redirect dengan pesan bahwa status otomatis.

---

## 5. UI / UX (halaman detail proyek)

Urutan konten (di bawah KPI kalkulasi yang sudah ada):

1. **Tiga kartu progres proyek**
   - Setor modal: sudah / target, %, sisa
   - Kembali modal: sudah / target, %, sisa
   - Kembali profit: sudah / target pool pemodal, %, sisa

2. **Kartu per pemodal**
   - Nama + target modal
   - Tiga metrik: setor %, kembali modal %, profit %
   - Tombol “+ Catat transaksi”

3. **Form / modal catat transaksi**
   - Pemodal (preselect jika dari kartu)
   - Jenis: Setor modal | Pengembalian modal | Pengembalian profit
   - Jumlah (default = sisa jenis itu, boleh dikurangi)
   - Tanggal (default hari ini)
   - Catatan opsional

4. **Riwayat transaksi**
   - Tabel: tanggal, pemodal, jenis, jumlah, catatan, aksi hapus

5. **Indikator status**
   - Ganti tombol “Tandai Selesai” dengan teks progres, mis. “Belum lunas — setor 1/2 pemodal penuh”
   - Saat lunas: badge “Selesai (otomatis)” + tanggal

Export PDF/Excel: fase 1 boleh tetap fokus kalkulasi; opsional ringkas “progres transaksi” di spek lanjutan (di luar MVP jika waktu terbatas). MVP: tampilkan di show page saja.

---

## 6. Komponen Teknis

| Unit | Tanggung jawab |
|------|----------------|
| Migration `CreateTransactionsTable` | Skema tabel |
| `TransactionModel` | CRUD, sum per jenis/investor/proyek |
| `TransactionService` (library) | Validasi sisa, hitung progres, evaluate auto-complete |
| `TransactionController` atau method di `ProjectController` | `store`, `delete` transaksi (auth + ownership) |
| `ProjectController::show` | Pass progres + riwayat ke view |
| `ProjectController::edit/update` | Blokir jika ada transaksi |
| Hapus `complete` manual | Sesuai §4.5 |
| View `projects/show.php` + CSS/JS | Kartu progres, form, riwayat |
| Tests | Unit service (sisa, overpay, auto-complete); feature store/delete |

Routes (auth filter):

```
POST /projects/(:num)/transactions
POST /projects/(:num)/transactions/(:num)/delete
```

---

## 7. Alur Data

```
Proyek tersimpan (investors + kalkulasi)
        │
        ▼
User catat transaksi (setor / kembali modal / profit)
        │
        ▼
TransactionService validasi ≤ sisa → insert
        │
        ▼
Evaluasi auto-complete → update projects.status
        │
        ▼
Show page: target dari kalkulator + SUM transaksi = progres
```

---

## 8. Error Handling

| Kasus | Perilaku |
|-------|----------|
| Overpay (jumlah > sisa) | Validasi gagal, pesan jelas, tidak insert |
| Investor salah proyek | 404 / validasi |
| Edit proyek saat ada transaksi | Redirect + flash error |
| Target profit 0 | Progress profit = 100%, tidak memblokir complete |
| Proyek rugi (tidak ada profit) | Hanya setor + pengembalian modal yang relevan; profit target 0 |

---

## 9. Testing

- Unit: `TransactionService` — sisa, penolakan overpay, complete saat semua lunas, reopen saat hapus transaksi
- Feature: store setor parsial, store pengembalian terpisah, delete, ownership, blokir edit setelah transaksi
- Tidak mengubah rumus `ProfitCalculator` kecuali memastikan output dipakai sebagai target

---

## 10. Di Luar Scope (MVP)

- Dashboard rekapan lintas proyek / filter “hari ini”
- Transaksi profit operator
- Pengeluaran beli barang (modal keluar ke supplier)
- Double-entry accounting
- Edit in-place transaksi (cukup hapus + buat baru)
- Notifikasi / bukti transfer upload

---

## 11. Migrasi Data Existing

Proyek yang sudah `completed` tanpa transaksi tetap `completed`. Tidak mengisi transaksi retrospektif otomatis. User bisa menambah transaksi di proyek `active`; proyek `completed` lama tetap read-only untuk edit angka, tetapi **boleh** menambah transaksi hanya jika kita buka kembali — keputusan MVP:

- Proyek `completed` yang sudah ada: **read-only penuh** (tidak wajib backfill transaksi).
- Auto-complete hanya berlaku ke depan untuk proyek yang memakai alur transaksi.

---

## 12. Sukses Criteria

1. Bisa mencatat setor bertahap per pemodal dan melihat % + sisa di detail proyek  
2. Bisa mencatat pengembalian modal dan profit sebagai jenis terpisah, bertahap  
3. Proyek otomatis `completed` saat semua pemodal lunas setor + pengembalian modal + profit  
4. Tidak ada tombol “Tandai Selesai” manual di alur baru  
5. Edit proyek terkunci setelah transaksi pertama  
