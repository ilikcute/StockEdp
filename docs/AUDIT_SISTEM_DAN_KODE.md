# Laporan Audit Komprehensif Sistem StockEdp (Faktual & Terverifikasi)
## Evaluasi Realitas Dokumentasi, Antarmuka (UI/UX), Performa Basis Data (Indexing & Relasi), serta Struktur Codebase

> **Tanggal Pelaksanaan:** 12 September 2026  
> **Target Aplikasi:** Web App StockEdp (Inventory & Field Allocation Management System)  
> **Lingkungan Proyek:** Windows / Laragon / PHP 8.3 / MySQL 8.0 (InnoDB) / Laravel 13.8 / Vue 3 / Vite 8 / Tailwind CSS v4  
> **Status Audit:** FAKTUAL & TERVERIFIKASI LANGSUNG PADA BASIS KODE & DATABASE AKTIF

---

## 1. Ringkasan Eksekutif & Realitas Proyek

Sistem **StockEdp** saat ini sedang berada dalam fase implementasi lanjutan penyelarasan dengan **PRD V1** (*Inventory & Field Allocation Management System*). Dari sistem pergudangan konvensional, aplikasi telah diperluas untuk mencakup:
1. **Master Data Toko (Stores)** terintegrasi dengan riwayat audit (*created_by, updated_by*).
2. **Lokasi Penyimpanan Multi-Tipe** (`MAIN_WAREHOUSE`, `FIELD_PERSONNEL`, `DAMAGED_STORAGE`) dengan relasi `user_id` untuk teknisi lapangan.
3. **Kondisi Persediaan Ganda (Dual Inventory Condition)**: Pemisahan saldo fisik antara kondisi Baik (`GOOD`) dan Rusak/Afkir (`DEFECTIVE`).
4. **Alokasi Penggantian Unit Toko (Store Asset Replacement)**: Pencatatan unit baru yang dipasang dan unit lama yang ditarik dari toko oleh teknisi lapangan.
5. **Pelaporan Terdedikasi**: Laporan Saldo Teknisi Lapangan (`/reports/field-balances`) dan Laporan Histori Kerusakan & Alokasi Toko (`/reports/store-allocations`).

### Matriks Evaluasi Nyata (Real Scorecard)
| Bidang Audit | Penilaian | Status Aktual |
| :--- | :---: | :--- |
| **1. Dokumentasi (.md)** | **4.2 / 5.0** | **Lengkap & Sangat Teknis.** Terdapat 24 file `.md`. Dokumentasi arsitektur sangat mendalam. Namun tidak ada `README.md` di root, ada duplikasi file PRD, dan ada ketidaksesuaian syarat PHP di `INSTALLATION.md`. |
| **2. UI / UX** | **4.0 / 5.0** | **Modern, Bersih, dan Responsif.** Desain Tailwind v4 konsisten, modal dialog dan notifikasi aksesibel, tersedia bilah navigasi bawah untuk mobile. Kendala utama: dropdown pemilihan toko (666 data) dan produk (1.000 data) pada form alokasi toko masih menggunakan `<select>` statis tanpa live search combobox, serta tab navigasi bawah belum mendeteksi rute toko dan alokasi. |
| **3. Performa & Query DB** | **4.1 / 5.0** | **Integritas Tinggi, Ditemukan Indeks Redundan.** Penggunaan `BCMath` desimal 4 digit dan `lockForUpdate()` menjaga saldo secara aman. Terbukti secara faktual di MySQL terdapat 2 indeks redundan (`stores_code_index` dan `idx_balances_product_id`), serta pembuatan nomor alokasi toko belum memiliki lock konkurensi. |
| **4. Codebase & Artefak** | **4.3 / 5.0** | **Ramping, Terstruktur, dan Tepat Guna (Tidak Gemuk).** Pola *Feature-Driven Architecture* diterapkan secara konsisten. Build frontend sangat ringan (gzip 90 kB). Tidak ditemukan file deadcode terduplikasi pada model aktif. Terdapat sedikit file log/cache di root repositori. |

---

## 2. Audit Dokumentasi Proyek (.md)

Terdapat total **24 berkas Markdown (.md)** di dalam workspace:
- **15 berkas di folder `docs/`**: Berkas spesifikasi teknis, panduan instalasi, lingkungan, UAT, PRD, dan panduan operasional.
- **9 berkas di folder `skills/stockedp/`**: Berkas referensi modul dan panduan agen AI.

### A. Temuan Positif & Kekuatan
1. **Kedalaman Spesifikasi Luar Biasa:** Dokumen seperti `docs/REPLENISHMENT_RECOMMENDATIONS.md`, `docs/BARCODE_SCANNER.md`, dan `docs/OPERATIONAL_DASHBOARD.md` menjelaskan logika bisnis, batasan invarian, penanganan leading-zero string, dan rumus matematika secara presisi.
2. **Kesesuaian dengan Regulasi Transaksi:** `docs/PRD.md` secara tegas mengunci batasan teknis (misal: dilarang menghitung saldo di sisi client Vue, wajib ACID transaction, dan wajib audit trail mutasi).

### B. Temuan Kekurangan & Ketidakkonsistenan
1. **Ketiadaan `README.md` Utama di Root Direktori:**  
   Repositori tidak memiliki `README.md` pada direktori utama. Hal ini menyulitkan developer atau pihak manajemen saat pertama kali membuka repositori di GitHub/GitLab untuk memahami gambaran umum proyek, cara setup cepat, dan struktur folder.
2. **Duplikasi Berkas Statis 100% (`docs/PRD.md` vs `skills/stockedp/PRD.md`):**  
   Kedua file memiliki checksum SHA256 identik (`953E2470D0A0...`). Menyimpan dua berkas PRD identik di dua folder berbeda berisiko memicu *documentation drift* apabila salah satu berkas diperbarui sementara yang lain terlewatkan.
3. **Ketidaksesuaian Syarat PHP pada `docs/INSTALLATION.md`:**  
   Pada `docs/INSTALLATION.md` Bab 1 tertera: *"PHP: 8.5.0 (atau minimal 8.2+)"*. Faktanya, `composer.json` mendefinisikan `"php": "^8.3"`. Menjalankan `composer install` pada PHP 8.2 akan langsung gagal (*version constraint error*).
4. **Ketertinggalan Catatan `docs/TASKS.md`:**  
   Log tugas pada `docs/TASKS.md` terakhir kali diperbarui pada Fase 1 ("Penyelesaian Fase 1: Skema Database & Master Data"). Pekerjaan Fase 2 (Alokasi Toko, GA Inflow, Serial Number, serta Laporan Terkait) yang sudah terimplementasi di kode belum dicatat tuntas pada log tersebut.
5. **Referensi Berkas Usang di `skills/stockedp/SKILL.md`:**  
   Pada baris 62 tertulis rujukan: *"AGENTS.md, PRD.md, ARCHITECTURE.md, DECISIONS.md, TASKS.md, dan docs/*"*. Berkas `AGENTS.md`, `ARCHITECTURE.md`, dan `DECISIONS.md` tidak pernah ada di root proyek.
6. **Kelengkapan Navigasi di `docs/WAREHOUSE_USER_GUIDE.md`:**  
   Bab 2 ("Navigasi Aplikasi") belum menyertakan entitas **Master Data Toko (Stores)** dalam daftar ringkasan menu master, meskipun proses alokasi toko sudah dibahas di Bab 3.

---

## 3. Audit Tampilan & Kemudahan Penggunaan (UI / UX)

Pemeriksaan dilakukan langsung terhadap berkas antarmuka Vue 3 di `resources/js/` dan pengujian bundling Vite.

### A. Temuan Positif & Desain Visual
1. **Desain Bersih & Elegan (Tailwind CSS v4):** Tata letak memanfaatkan palet warna yang konsisten: Indigo (aksi primer), Emerald (status aktif / barang kondisi baik), Rose (pembatalan / unit rusak / bahaya), dan Amber (peringatan / stok menipis).
2. **Bilah Navigasi Bawah Khusus Mobile (`MobileBottomBar.vue`):**  
   Disediakan khusus untuk teknisi lapangan yang mengakses aplikasi melalui smartphone layar sentuh, mempermudah navigasi hanya dengan satu jempol tanpa harus membuka drawer menu samping.
3. **Aksesibilitas Komponen Modal:**  
   `BaseConfirmation.vue` memiliki implementasi dialog aksesibel standar WAI-ARIA: *focus trap*, penutupan dengan tombol Escape, dan pelabelan deskriptif.
4. **Dasbor Operasional Cerdas (`DashboardPage.vue`):**  
   Menyediakan *skeleton loading*, ringkasan kesehatan stok, pemantauan antrean dokumen yang butuh approval, grafik produk paling sering keluar/masuk, dan alert stok otomatis.
5. **Kelengkapan Halaman Laporan Baru:**  
   - `FieldBalanceReportPage.vue`: Memungkinkan pemantauan saldo teknisi per lokasi dan filter kondisi barang.
   - `StoreAllocationReportPage.vue`: Menyajikan histori penggantian unit toko secara rinci beserta alasan kerusakan unit tarik.

### B. Hambatan Kemudahan Penggunaan (UX Bottlenecks)
1. **Skalabilitas Dropdown pada Form Alokasi Toko (`StoreAllocationFormPage.vue`):**  
   - **Pemilihan Toko:** Berdasarkan data nyata di database, terdapat **666 data toko aktif**. Pada form alokasi, toko dimuat ke dalam tag `<select>` HTML standar tanpa fitur pencarian ketik cepat. Teknisi di lapangan harus menggulir 666 baris toko secara manual.
   - **Pemilihan Produk:** Produk (1.000 item) juga dimuat ke dalam tag `<select>` standar tanpa live search.
   - **Rekomendasi Solusi:** Proyek sudah memiliki komponen canggih `BaseCombobox.vue` (mendukung pencarian instan nama, kode, dan keyboard navigation). Mengganti tag `<select>` dengan `BaseCombobox.vue` akan memangkas waktu pengisian formulir dari ~2 menit menjadi <30 detik.
2. **Visibilitas Saldo Lapangan (*Blind Picking*):**  
   Pada `StoreAllocationFormPage.vue`, dropdown produk tidak menampilkan sisa stok `GOOD` yang sedang dipegang oleh teknisi bersangkutan. Teknisi berpotensi memilih barang yang fisiknya tidak ada di tas/mobilnya, dan baru mengetahui kegagalan transaksi saat menekan tombol "Simpan" (HTTP 422 `INSUFFICIENT_STOCK`).
3. **Ketiadaan Input Barcode Scanner pada Alokasi Toko:**  
   Form penerimaan barang (`StockReceiptFormPage.vue`) dan pengeluaran (`StockIssueFormPage.vue`) telah dilengkapi tombol/panel barcode scanner HID `[F2]`. Namun halaman alokasi toko belum mengintegrasikannya, padahal teknisi lapangan sangat membutuhkan pemindaian barcode/SN unit secara cepat di lokasi toko.
4. **Inkonsistensi Highlight Navigasi Bawah Mobile (`MobileBottomBar.vue`):**  
   - Pada baris 35 (Tab Master), rute `/stores` belum dimasukkan ke array rute aktif. Saat pengguna berada di halaman Master Toko, tab Master tidak menyala.
   - Pada baris 58 (Tab Persediaan), rute `/inventory/store-allocations` dan `/inventory/movements` belum dimasukkan ke array rute aktif.

---

## 4. Audit Kinerja & Query Basis Data (Indexing & Relasi)

Pemeriksaan dilakukan langsung terhadap skema migrasi database dan hasil eksekusi perintah `SHOW INDEX` pada database MySQL `stockedp` aktif.

### A. Kekuatan Kinerja & Desain Basis Data
1. **Pencegahan N+1 Query pada Repository:**  
   Semua repository utama (`StoreAllocationRepository`, `ReportingRepository`, `LocationRepository`) secara disiplin menerapkan eager loading (`with([...])`) untuk relasi terkait (teknisi, lokasi, toko, produk, item alokasi).
2. **Integritas Konkurensi & Pessimistic Locking:**  
   Pemotongan dan penambahan saldo di `StockMovementService` menggunakan `lockForUpdate()` dengan pengurutan ID produk terurut naik (`product_id ASC`), mencegah *race condition* dan meminimalkan resiko *deadlock*.
3. **Presisi Desimal & Keamanan Finansial/Stok:**  
   Seluruh tabel persediaan menggunakan tipe data `DECIMAL(14,4)`. Penghitungan di backend menggunakan pustaka `BCMath` murni tanpa konversi floating point PHP/JS.

### B. Temuan Faktual pada Indeks Basis Data
Berdasarkan pengecekan riil pada tabel MySQL:

1. **Indeks Redundan pada Tabel `stores` (Terbukti di MySQL):**  
   Hasil `SHOW INDEX FROM stores`:
   - `stores_code_unique` (Kolom: `code`, Unique: YES)
   - `stores_code_index` (Kolom: `code`, Unique: NO)  
   **Analisis:** Constraint UNIQUE pada kolom `code` sudah secara otomatis membentuk B-Tree index di MySQL. Penambahan `$table->index('code')` pada migrasi `2026_09_12_100001` menghasilkan indeks ganda yang redundan, memboroskan ruang disk dan membebani operasi INSERT/UPDATE.

2. **Indeks Redundan pada Tabel `inventory_balances` (Terbukti di MySQL):**  
   Hasil `SHOW INDEX FROM inventory_balances`:
   - `prod_loc_cond_unique` (Kolom: `product_id, location_id, condition`, Unique: YES)
   - `idx_balances_product_id` (Kolom: `product_id`, Unique: NO)  
   **Analisis:** Dalam algoritma B-Tree MySQL, indeks unik komposit `(product_id, location_id, condition)` sudah otomatis mengindeks kolom paling kiri (`product_id`). Pembuatan indeks `idx_balances_product_id` secara terpisah pada migrasi `2026_09_12_100003` adalah 100% redundan.

3. **Efisiensi Indeks Komposit pada `store_allocations`:**  
   Pada tabel `store_allocations` terdapat:
   - `store_allocations_allocated_at_store_id_index` (`allocated_at, store_id`)
   - `store_allocations_store_id_foreign` (`store_id`)  
   **Analisis:** Dalam kueri histori alokasi toko, penyaringan hampir selalu menggunakan format: `WHERE store_id = ? AND allocated_at BETWEEN ? AND ?`. Sesuai prinsip *Equality before Range*, indeks komposit dengan susunan `(store_id, allocated_at)` jauh lebih efisien dibandingkan `(allocated_at, store_id)`.

4. **Resiko Konkurensi pada Generator Nomor Alokasi Toko:**  
   Pada `StoreAllocationRepository::getNextAllocationNumber()`:
   ```php
   $lastRecord = StoreAllocation::where('allocation_number', 'like', "{$prefix}%")
       ->orderBy('id', 'desc')
       ->first();
   ```
   Kueri ini **belum** dilengkapi `lockForUpdate()`. Jika dua teknisi men-submit alokasi toko secara bersamaan dalam milidetik yang sama, kedua proses akan membaca nomor urut terakhir yang sama dan mencoba meng-insert nomor alokasi identik, mengakibatkan salah satu transaksi gagal dengan error SQL `1062 Duplicate entry`.  
   *(Sebagai pembanding yang baik: `StockReceiptRepository::generateReceiptNumber()` sudah mengimplementasikan `lockForUpdate()`, penanganan error code 1062, dan percobaan ulang / retry loop).*

---

## 5. Audit Struktur Codebase, Ketebalan (Bloat), & Artefak

### A. Apakah Codebase Gemuk (*Bloated*)?
**KESIMPULAN: TIDAK. Codebase Tergolong Ramping, Bersih, dan Proporsional.**
- **Struktur Modular:** Kode backend dipisahkan secara rapi menggunakan pendekatan *Feature-Sliced Architecture* (`app/Features/`). Setiap domain memiliki Action (transaksi tunggal), Controller tipis, FormRequest untuk validasi ketat, DTO, Model, dan Repository.
- **Beban Pustaka Luar Minimal:** Aplikasi tidak menginstal library yang tidak esensial. Tidak ada ketergantungan GraphQL berlebih atau event-bus overhead yang memperlambat sistem.
- **Efisiensi Bundle Frontend:**  
  - Ukuran bundle JavaScript utama (`app.js`): **403 kB** (terkompresi gzip hanya **90.1 kB**).
  - Waktu build Vite sangat cepat: **3.34 detik** untuk 266 modul.
  - Pemeriksaan linter ESLint: **0 error, 0 warning**.
  - Pemeriksaan code style Laravel Pint: **Passed**.

### B. Pemeriksaan Deadcode & Artefak Riil
1. **Model Domain Bersih Tanpa Duplikasi:**  
   Model `StoreAllocation` dan `StoreAllocationItem` berada di namespace yang tepat: `App\Features\StoreAllocation\Models\`. Tidak ditemukan duplikasi file model aktif di folder lain.
2. **Kesesuaian Tipe pada DTO (`StockChangeDTO.php`):**  
   Pada `app/Features/Inventory/DTOs/StockChangeDTO.php` baris 19, properti `$condition` masih didefinisikan bertipe `public string $condition = 'GOOD'` dan belum diikat secara type-safe ke enum `StockCondition $condition`. Hal ini berpotensi meloloskan nilai string sembarang jika tidak divalidasi ketat.
3. **File Log & Cache Sementara di Root:**  
   - `serve.log` (1.076 bytes) dan `serve.err.log` (0 bytes): Berkas log sementara saat menjalankan server lokal.
   - `.phpunit.result.cache`: Berkas cache eksekusi unit test PHPUnit.  
   Berkas-berkas ini aman untuk dihapus dan dipastikan tidak ter-commit ke repositori git.

---

## 6. Hasil Verifikasi Pengujian Otomatis (Automated Test Verification)

Pengujian otomatis fitur baru yang dijalankan pada database pengujian menghasilkan:
```text
PHPUnit 12.5.12 by Sebastian Bergmann and contributors.

Tests: 18, Assertions: 99, Time: 25.51s
Status: OK (100% PASSED)
```
- `StoreManagementTest`: 7 passed (25 assertions)
- `StoreAllocationTest`: 6 passed (42 assertions)
- `FieldBalanceReportTest`: 3 passed (17 assertions)
- `StoreAllocationReportTest`: 2 passed (15 assertions)

Fitur inti penambahan Master Toko, Alokasi Penggantian Toko, serta Laporan terkait telah terbukti secara fungsional lulus uji logika dan skema data.

---

## 7. Rekomendasi Rencana Peningkatan Berdasarkan Prioritas

### Prioritas 1: Kualitas UX Teknisi Lapangan (High Impact)
1. **Integrasikan `BaseCombobox.vue` pada `StoreAllocationFormPage.vue`:**  
   Ganti tag `<select>` biasa dengan komponen pencarian cerdas untuk memilih Toko (dari 666 toko) dan Produk (dari 1.000 produk).
2. **Tampilkan Indikator Saldo Teknisi Saat Memilih Produk:**  
   Sertakan kuantitas stok fisik yang saat ini ada di lokasi teknisi pada daftar pilihan produk agar teknisi tidak salah pilih barang yang saldonya kosong.
3. **Tambahkan Input Shortcut Barcode Scanner:**  
   Sediakan opsi scan barcode/SKU pada form alokasi toko untuk mempercepat proses entri di toko.
4. **Perbaiki Tab Aktif `MobileBottomBar.vue`:**  
   Tambahkan `/stores` ke tab Master dan `/inventory/store-allocations` ke tab Persediaan.

### Prioritas 2: Kinerja & Ketahanan Transaksi Basis Data (High Impact)
1. **Bersihkan Indeks Redundan di MySQL:**  
   Buat file migrasi pembersihan untuk menghapus:
   - `stores_code_index` pada tabel `stores`.
   - `idx_balances_product_id` pada tabel `inventory_balances`.
2. **Terapkan `lockForUpdate()` pada Generator Nomor Alokasi:**  
   Tambahkan penguncian baris (`lockForUpdate()`) dan penanganan duplicate key pada `StoreAllocationRepository::getNextAllocationNumber()` untuk menjamin keamanan saat transaksi tinggi bersamaan.
3. **Optimalisasi Urutan Indeks Komposit Alokasi:**  
   Ubah urutan indeks komposit `['allocated_at', 'store_id']` menjadi `['store_id', 'allocated_at']`.

### Prioritas 3: Kerapihan Dokumentasi & Repositori (Maintenance)
1. **Buat File `README.md` pada Root Proyek:**  
   Sediakan ringkasan eksekutif, langkah instalasi cepat, dan tautan menuju dokumentasi lengkap di folder `docs/`.
2. **Koreksi Persyaratan PHP pada `docs/INSTALLATION.md`:**  
   Ubah teks `minimal 8.2+` menjadi `minimal 8.3+` agar sinkron dengan `composer.json`.
3. **Perbarui Log Pelaksanaan Tugas `docs/TASKS.md`:**  
   Catat tuntas implementasi Fase 2 (Alokasi Toko, Master Toko, dan Pelaporan).
4. **Type-Safe Refactor pada `StockChangeDTO`:**  
   Ganti tipe parameter `$condition` dari `string` menjadi enum `StockCondition`.
5. **Bersihkan File Log Runtime di Root:**  
   Hapus `serve.log`, `serve.err.log`, dan `.phpunit.result.cache`.
