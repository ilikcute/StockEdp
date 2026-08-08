# Matriks User Acceptance Testing (UAT Version 1) — DOCUMENT PREPARED

Dokumen ini berisi rencana dan matriks pengujian penerimaan pengguna (UAT) untuk Sistem Inventory Version 1. Status dokumen saat ini adalah **DOCUMENT PREPARED (READY FOR FASE 10C EXECUTION)**.

> **Catatan Verifikasi Otomatis (dieksekusi 08-Agu-2026):**
> Backend release verification suite (`ReleaseDatasetIntegrityTest`, 56 test) dinyatakan **PASS 56/56** pada database rehearsal bersih (`InventorySystemRehearsal`) dengan `./vendor/bin/pint --test` lulus 0 issue. Verifikasi manual matriks di bawah (UAT-01 s.d UAT-12) masih menunggu eksekusi Stage 10C oleh pengguna peran nyata.

---

## 1. Prekondisi Akun Pengguna UAT

> **CATATAN CREDENTIAL SANGAT PENTING**:  
> - `ReleaseVerificationSeeder` **TIDAK** menyediakan password plaintext tetap maupun akun login administrator default.  
> - **Akun Administrator UAT**: Wajib dibuat sebelum UAT menggunakan command interaktif CLI: `php artisan app:create-initial-admin`.  
> - **Akun Petugas Gudang (Warehouse Officer) & Supervisor**: Dibuat melalui menu Pengelolaan Pengguna (User Management) oleh Administrator atau melalui seeder/fixture khusus lingkungan uji.  
> - **Akses Lokasi**: Administrator dan Pengguna wajib diberikan hak akses lokasi yang sesuai sebelum skenario UAT dieksekusi.

---

## 2. Matriks Skenario UAT

| ID | Role Test | Precondition | Langkah Pengujian | Hasil yang Diharapkan | Status Verification |
| --- | --- | --- | --- | --- | :---: |
| **UAT-01** | All Roles | Seeder Role/Permission aktif | Login sebagai Admin, Petugas, dan Supervisor | Menu & UI memuat permission granular secara akurat; endpoint terlarang memicu HTTP 403 | **NOT VERIFIED** |
| **UAT-02** | Admin | Admin Authenticated | Menambah & mengubah Produk, Kategori, Satuan, Supplier, dan Lokasi | Data master tersimpan presisi; pencarian & pagination berfungsi lancar | **NOT VERIFIED** |
| **UAT-03** | Petugas Gudang | Master data & Lokasi tersedia | Membuat Draft & Posting Penerimaan Stok (Stock Receipt) | Status `POSTED`, saldo bertambah, `StockMovement` bertipe `RECEIPT` tercatat | **NOT VERIFIED** |
| **UAT-04** | Petugas Gudang | Saldo stok tersedia | Membuat Pengeluaran Stok (Stock Issue) melebihi & kurang dari saldo | Pengeluaran mencukupi berhasil (`ISSUE`); Pengeluaran melebihi stok ditolak HTTP 422 tanpa partial commit | **NOT VERIFIED** |
| **UAT-05** | Petugas Gudang | Saldo di Gudang Utama | Transfer Stok dari Gudang Utama ke Gudang Cabang (Send -> Receive) | Status `SENT` memicu `TRANSFER_OUT`; Status `RECEIVED` memicu `TRANSFER_IN` | **NOT VERIFIED** |
| **UAT-06** | Petugas & Supervisor | Draft Adjustment tersedia | Petugas buat Draft Adjustment -> Supervisor Post Adjustment | Maker-checker berjalan; status `POSTED`; `ADJUSTMENT_IN` / `ADJUSTMENT_OUT` tercatat | **NOT VERIFIED** |
| **UAT-07** | Petugas & Supervisor | Lokasi aktif | Siklus Opname (Start -> Count -> Complete -> Post) | Snapshot saldo terkunci; fisik terhitung; penyesuaian opname surplus/shortage diposting | **NOT VERIFIED** |
| **UAT-08** | All Roles | Transaksi terisi | Membuka Laporan Saldo, Stok Minimum, Kartu Stok, dan Laporan Transaksi | Filter lokasi/produk/kategori/tanggal presisi; pagination cepat; data akurat | **NOT VERIFIED** |
| **UAT-09** | Supervisor | Permission export | Mengunduh CSV untuk seluruh 8 jenis laporan | File CSV ber-BOM UTF-8 terunduh synchronous; header Bahasa Indonesia; formula injection ter-escape | **NOT VERIFIED** |
| **UAT-10** | Admin | Record movement ada | Menelusuri record pergerakan stok | Terlacaknya produk, lokasi, type, `quantity_before`, `quantity_after`, reference_type, user_id, timestamp | **NOT VERIFIED** |
| **UAT-11** | All Roles | API rate limiter | Mengirim > 60 request/menit | Respons HTTP 429 dengan header `Retry-After`; UI tidak logout dan form tidak terreset | **NOT VERIFIED** |
| **UAT-12** | All Roles | Variasi resolusi layar | Akses aplikasi pada viewport desktop ($1280 \times 800$) dan tablet ($1024 \times 768$) | Tampilan responsive, modal dialog pas, tabel tidak terpotong | **NOT VERIFIED** |
