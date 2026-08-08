# Daftar Tugas — Inventory System

Dokumen ini berisi pekerjaan yang sudah disetujui untuk Sistem Inventory.

## Aturan Penggunaan

- Kerjakan tugas berdasarkan urutan fase.
- Hanya satu tugas utama yang boleh berstatus sedang dikerjakan.
- Jangan mengerjakan fitur yang berada di luar ruang lingkup `PRD.md`.
- Jangan menambahkan dependency tanpa persetujuan.
- Jangan memulai implementasi yang masih memiliki keputusan terbuka.
- Tandai tugas selesai hanya setelah kode, test, dan lint berhasil.
- Keputusan teknis penting harus dicatat dalam `DECISIONS.md`.

Status:

- `[ ]` Belum dikerjakan
- `[-]` Sedang dikerjakan
- `[x]` Selesai
- `[!]` Terblokir

---

## Fase 0 — Keputusan Sebelum Implementasi

- [x] Tentukan apakah pengguna dapat mengakses semua lokasi atau lokasi tertentu (Lokasi berbasis hak akses `user_locations` dan Administrator global).
- [x] Tentukan apakah transfer menggunakan alur `DRAFT → SENT → RECEIVED`.
- [x] Tentukan apakah transaksi inventory membutuhkan approval (Maker-Checker untuk Stock Adjustment dan Opname).
- [x] Tentukan apakah produk mendukung konversi satuan (Satu satuan per produk untuk Version 1).
- [x] Tentukan apakah barcode wajib atau opsional (Barcode bersifat opsional namun unik).
- [x] Putuskan inventory valuation untuk Version 1: NOT APPLICABLE / deferred.
- [x] Tentukan apakah transaksi dibekukan selama stock opname berlangsung (`LOCATION_FROZEN` lock guard).
- [x] Tentukan format ekspor laporan: CSV UTF-8 dengan BOM secara synchronous streaming.
- [x] Tentukan mekanisme autentikasi Laravel Sanctum: token-based API authentication.
- [x] Catat setiap keputusan yang sudah final dalam `DECISIONS.md`.

Fase ini selesai ketika seluruh pertanyaan yang memengaruhi struktur database
dan alur transaksi sudah diputuskan.

---

## Fase 1 — Fondasi Proyek

### Backend Laravel

- [x] Buat atau verifikasi proyek Laravel.
- [x] Konfigurasikan koneksi MySQL melalui `.env`.
- [x] Konfigurasikan timezone `Asia/Jakarta` dan locale `id`.
- [x] Buat prefix REST API `/api/v1`.
- [x] Buat struktur `app/Features/` dan `app/Shared/`.
- [x] Buat Service Provider untuk mendaftarkan route setiap fitur.
- [x] Buat format response API yang konsisten.
- [x] Buat exception handler untuk validation dan domain error.
- [x] Konfigurasikan database session, cache, dan queue.
- [x] Buat health-check endpoint.
- [x] Tambahkan test untuk health-check dan format response API.

### Frontend Vue

- [x] Buat atau verifikasi proyek Vue 3.
- [x] Konfigurasikan Pinia.
- [x] Konfigurasikan Vue Router.
- [x] Buat struktur `src/features/` dan `src/shared/`.
- [x] Buat shared API client.
- [x] Konfigurasikan `VITE_API_BASE_URL` dan timeout API.
- [x] Buat normalisasi response dan error API.
- [x] Buat layout dasar aplikasi.
- [x] Buat halaman `404 Not Found`.
- [x] Buat loading, empty, error, dan confirmation component.
- [x] Pastikan tidak ada secret dalam variable `VITE_*`.

### Verifikasi Fase

- [x] Jalankan `php artisan test`.
- [x] Jalankan `./vendor/bin/pint`.
- [x] Jalankan `npm run lint`.
- [x] Catat perubahan arsitektur dalam `DECISIONS.md` jika ada.

---

## Fase 2 — Autentikasi dan Hak Akses

### Backend

- [x] Buat migration dan Model pengguna.
- [x] Implementasikan login dan logout.
- [x] Implementasikan endpoint pengguna yang sedang login.
- [x] Implementasikan mekanisme autentikasi yang sudah diputuskan.
- [x] Buat role Administrator, Petugas Gudang, dan Supervisor Inventory.
- [x] Buat permission untuk setiap operasi inventory.
- [x] Implementasikan Policy atau authorization pada endpoint.
- [x] Tambahkan status aktif/nonaktif pengguna.
- [x] Catat login dan aktivitas penting pengguna.
- [x] Tambahkan test autentikasi dan authorization.

### Frontend

- [x] Buat halaman login.
- [x] Buat `use_auth_store`.
- [x] Buat route guard untuk halaman terproteksi.
- [x] Buat navigasi berdasarkan permission.
- [x] Buat halaman profil pengguna.
- [x] Tangani session habis dan response `401`.
- [x] Tangani akses ditolak dengan response `403`.
- [x] Tambahkan test untuk auth store dan route guard.

### Verifikasi Fase

- [x] Pastikan pengguna tanpa autentikasi tidak dapat mengakses API terproteksi.
- [x] Pastikan menu dan endpoint mengikuti permission.
- [x] Jalankan seluruh test dan lint.

---

## Fase 3 — Master Data

### Category dan Unit

- [x] Buat migration, Model, DTO, Repository, Action, dan API kategori.
- [x] Buat migration, Model, DTO, Repository, Action, dan API satuan.
- [x] Implementasikan validasi kode dan nama (pada Kategori).
- [x] Cegah penghapusan data yang sudah digunakan (pada Kategori nonaktifkan via PATCH /status).
- [x] Buat halaman daftar dan form kategori.
- [x] Buat halaman daftar dan form satuan.
- [x] Tambahkan pencarian, pagination, dan status aktif (pada Kategori).
- [x] Tambahkan test backend dan frontend (pada Kategori).

### Product

- [x] Buat migration produk.
- [x] Tambahkan SKU unik, nama, kategori, satuan, dan status.
- [x] Tambahkan barcode sesuai keputusan produk.
- [x] Tambahkan batas minimum stok.
- [x] Implementasikan Repository dan query pencarian produk.
- [x] Implementasikan Action membuat dan memperbarui produk.
- [x] Implementasikan API Resource produk.
- [x] Buat endpoint daftar, detail, tambah, dan ubah produk.
- [x] Buat halaman daftar produk.
- [x] Buat halaman form produk.
- [x] Tambahkan pencarian berdasarkan nama, SKU, dan barcode.
- [x] Tambahkan test constraint SKU dan barcode.

### Supplier dan Lokasi

- [x] Buat fitur master supplier.
- [x] Buat fitur lokasi penyimpanan.
- [x] Tambahkan pembatasan akses lokasi sesuai keputusan.
- [x] Cegah penghapusan lokasi yang sudah memiliki transaksi.
- [x] Buat halaman supplier.
- [x] Buat halaman lokasi penyimpanan.
- [x] Tambahkan test backend dan frontend.

### Verifikasi Fase

- [x] Pastikan master data yang sudah digunakan tidak dapat dihapus permanen.
- [x] Pastikan seluruh daftar menggunakan server-side pagination.
- [x] Jalankan seluruh test dan lint.

---

### Fase 4: Inti Persediaan

- [x] **Fase 4A: Saldo dan Pergerakan (Stock Balance & Movement Engine)**
- [x] **Fase 4B: Penerimaan Stok Multi-item (Stock Receipt)**
- [x] **Fase 4C: Pengeluaran Stok Multi-item (Stock Issue)**
- [x] **Fase 4D: Testing & Edge Cases (Concurrency, Deadlock Prevention, Negative Stock)**
- [x] Tambahkan nomor referensi dan jenis sumber transaksi.
- [x] Tambahkan pengguna dan timestamp pada movement.
- [x] Tambahkan index untuk produk, lokasi, tanggal, dan referensi.
- [x] Tambahkan unique constraint untuk mencegah duplikasi movement.
- [x] Buat enum jenis stock movement.
- [x] Buat `StockAvailabilityService`.
- [x] Buat `StockMovementService`.
- [x] Implementasikan row locking saldo stok.
- [x] Implementasikan pencegahan stok negatif.
- [x] Tambahkan test integritas dan transaksi bersamaan.

### Penerimaan Stok

- [x] Buat dokumen dan detail penerimaan stok.
- [x] Buat `ReceiveStockAction`.
- [x] Validasi lokasi, produk, dan quantity.
- [x] Simpan penerimaan dan movement `RECEIPT` dalam satu transaction.
- [x] Buat endpoint daftar, detail, dan posting penerimaan.
- [x] Buat halaman daftar penerimaan.
- [x] Buat form penerimaan multi-item.
- [x] Cegah pengiriman form berulang.
- [x] Tambahkan test rollback jika salah satu item gagal.

### Pengeluaran Stok

- [x] Buat dokumen dan detail pengeluaran stok.
- [x] Buat `IssueStockAction`.
- [x] Validasi ketersediaan stok sebelum posting.
- [x] Simpan pengeluaran dan movement `ISSUE` dalam satu transaction.
- [x] Buat endpoint daftar, detail, dan posting pengeluaran.
- [x] Buat halaman daftar pengeluaran.
- [x] Buat form pengeluaran multi-item.
- [x] Tampilkan pesan stok tidak mencukupi.
- [x] Tambahkan test pencegahan stok negatif.

### Verifikasi Fase

- [x] Pastikan saldo sesuai dengan akumulasi movement.
- [x] Pastikan movement yang diposting tidak dapat dihapus atau diedit.
- [x] Pastikan transaksi gagal tidak meninggalkan saldo parsial.
- [x] Jalankan seluruh test dan lint.

---

## Fase 5 — Transfer Stok

- [x] **Fase 5A: Database, Domain, dan Backend API Transfer Stok**
  - [x] Schema `stock_transfers` & `stock_transfer_items` dengan presisi `DECIMAL(14,4)`.
  - [x] PHP Backed Enum `TransferStatus` (DRAFT, SENT, RECEIVED, CANCELED).
  - [x] Domain Actions: Create, Update, Send, Receive, Cancel.
  - [x] Warehouse Authorization Enforcement pada Policy, Request, Repository, dan Action.
  - [x] Active Master Validation pada Create/Update (Location & Product aktif).
  - [x] Unique Transfer Number generator bergaransi `1062` retry handling.
  - [x] Batch Active Checking & Atomic In-transit state transition.
  - [x] Process-isolated MySQL Concurrency Integration Tests.
- [x] **Fase 5B: Frontend Transfer Stok**
  - [x] API Client & Pinia Store (`stockTransferApi`, `useStockTransferStore`).
  - [x] Navigation & Route Guards (`/inventory/transfers`, `/create`, `/:id`, `/:id/edit`).
  - [x] Halaman Daftar Transfer & Server-side Pagination (`StockTransferListPage`).
  - [x] Filter Status & Tab Cepat In-Transit (`SENT` status).
  - [x] Form Create & Edit Draft Transfer (`StockTransferFormPage`).
  - [x] Validasi Frontend (Origin vs Dest, Item duplikat, Quantity presisi string).
  - [x] Halaman Detail Transfer & Permission Abilities Matrix (`StockTransferDetailPage`).
  - [x] Action Send dengan Confirmation Modal & Error Handling (403, 409, 422).
  - [x] Action Receive dengan Confirmation Modal & Error Handling (403, 409, 422).
  - [x] Action Cancel dengan Confirmation Modal & Error Handling (403, 409, 422).
- [x] Validasi lokasi asal dan tujuan tidak sama.
- [x] Validasi stok tersedia di lokasi asal.
- [x] Buat movement `TRANSFER_OUT`.
- [x] Buat movement `TRANSFER_IN`.
- [x] Buat endpoint daftar, detail, buat, kirim, dan terima transfer.
- [x] Buat halaman daftar transfer.
- [x] Buat form transfer.
- [x] Buat halaman detail dan status transfer.
- [x] Tambahkan test transfer berhasil, gagal, dan concurrent.
- [x] Pastikan tidak ada stok yang bertambah atau hilang tanpa movement.
- [x] Jalankan seluruh test dan lint.

---

## Fase 6 — Stock Adjustment

- [x] **Fase 6A: Backend Stock Adjustment & Testing**
  - [x] Migration `stock_adjustments`, `stock_adjustment_items`, dan unique business movement constraint di `stock_movements`.
  - [x] `AdjustmentReason` Enum (FOUND, DAMAGED, EXPIRED, RECORDING_ERROR, ADMINISTRATIVE, LOST, OTHER) & `AdjustmentStatus` Enum.
  - [x] Domain Actions: `CreateStockAdjustmentAction`, `UpdateStockAdjustmentAction`, `PostStockAdjustmentAction`, `CancelStockAdjustmentAction`.
  - [x] Maker-Checker Enforcement: `posted_by != created_by` (pembuat tidak boleh post miliknya sendiri).
  - [x] Role Matrix & Granular Permissions (`stock_adjustments.view`, `.create`, `.update`, `.post`, `.cancel`).
  - [x] Active Master Validation & Reason-Direction compatibility guard.
  - [x] Single-direction constraint per document (INCREASE -> ADJUSTMENT_IN, DECREASE -> ADJUSTMENT_OUT).
  - [x] Concurrency-safe number generator (`ADJ-YYYYMM-XXXX`) dengan `lockForUpdate` & handling retry 1062.
  - [x] Multi-item atomic transaction & row locking.
  - [x] REST API Controller & Endpoints (index, store, show, update, post, cancel).
  - [x] Feature tests & MySQL Process-Isolated Concurrency Integration Tests.
- [x] **Fase 6B: Frontend Stock Adjustment**
  - [x] Buat halaman daftar adjustment dengan server-side pagination, filter, & search.
  - [x] Buat form adjustment multi-item dengan validation helper reason-direction compatibility & notes OTHER mandatory.
  - [x] Tambahkan konfirmasi sebelum posting & cancel modal dengan peringatan immutability & delta quantity.
  - [x] Integrasi Pinia Store & API client (`stockAdjustmentApi.js`, `useStockAdjustmentStore.js`).
  - [x] Document abilities & Maker-Checker UX (menggunakan abilities backend tanpa self-post calculation di frontend).
  - [x] Route guard (`stock_adjustments.view/.create/.update`) & AppLayout navigation menu.
- [x] Pastikan adjustment yang diposting tidak dapat dihapus (immutable).
- [x] Jalankan seluruh test dan lint.

---

## Fase 7 — Stock Opname

- [x] **Fase 7A: Freeze Infrastructure & Global Lock Ordering**
  - [x] Buat migration `inventory_location_locks` dengan CHECK constraint & auto-populasi lokasi existing.
  - [x] Buat `LocationObserver` untuk memastikan lokasi baru otomatis memiliki lock row.
  - [x] Buat `InventoryFreezeService` terpusat (`ensureLockRowsExist`, `lockAndValidateLocations`, `freezeLocation`, `unfreezeLocation`).
  - [x] Integrasikan freeze guard ke `StockMovementService` dengan global lock ordering (Dokumen -> Location Lock ASC -> Balance Lock ASC -> Movement).
  - [x] Tangani penolakan mutasi pada lokasi ter-freeze dengan HTTP 409 `LOCATION_FROZEN`.
  - [x] Buat `FreezeInfrastructureTest` (10 skenario feature & regression test).
  - [x] Buat `ConcurrencyFreezeTest` (4 skenario MySQL process-isolated concurrency test).
- [x] **Fase 7B: Backend & Frontend Stock Opname**
  - [x] Buat migration `stock_opnames`, `stock_opname_items`, & active location uniqueness constraint.
  - [x] Definisikan status `DRAFT`, `IN_PROGRESS`, `COUNTED`, `POSTED`, `CANCELED`.
  - [x] Buat Actions (`StartStockOpnameAction`, `InputCountAction`, `CompleteStockOpnameAction`, `ReopenStockOpnameAction`, `PostStockOpnameAction`, `CancelStockOpnameAction`).
  - [x] Buat Resource & Blind Count API contract (`snapshot_quantity` & `variance_quantity` disembunyikan saat `IN_PROGRESS`).
  - [x] Integrasikan `OPNAME_IN` dan `OPNAME_OUT` movement.
  - [x] Buat Frontend Pages (List, Form, Detail, Blind Count Workspace, Review Variance).
- [x] Pastikan saldo tidak ditimpa tanpa movement.
- [x] Jalankan seluruh test dan lint.

---

## Fase 8 — Saldo, Kartu Stok, dan Laporan
**Fase 8C — PASS WITH CLEAN AUDIT**

- [x] Fase 8B2 — Frontend Transaction Reports
- [x] Fase 8C1 — Backend CSV Export
- [x] Fase 8C2 — Frontend CSV Export
- [x] Fase 8C Final Audit
- [x] Buat endpoint saldo stok per produk dan lokasi.
- [x] Buat endpoint kartu stok dengan pagination.
- [x] Buat filter periode, produk, kategori, lokasi, dan jenis movement.
- [x] Buat laporan produk di bawah stok minimum.
- [x] Buat laporan penerimaan dan pengeluaran.
- [x] Buat laporan transfer.
- [x] Buat laporan adjustment.
- [x] Buat laporan hasil stock opname.
- [x] Implementasikan ekspor sesuai format yang telah disetujui.
- [x] Buat halaman saldo stok.
- [x] Buat halaman kartu stok.
- [x] Buat halaman laporan dan filter.
- [x] Pastikan query laporan menggunakan index yang sesuai.
- [x] Tambahkan test filter, pagination, authorization, dan ekspor.
- [x] Jalankan seluruh test dan lint.

---

## Fase 9 — Audit, Keamanan, dan Stabilitas
**Fase 9 — PASS WITH CLEAN AUDIT**

- [x] Audit seluruh endpoint untuk authentication dan authorization.
- [x] Audit seluruh Form Request dan domain validation.
- [x] Audit mass assignment pada Model.
- [x] Audit query untuk masalah N+1.
- [x] Audit transaksi stok untuk race condition.
- [x] Audit kemungkinan duplicate submission.
- [x] Audit penggunaan decimal dan larangan float.
- [x] Audit data sensitif pada log dan response API.
- [x] Pastikan `.env` tidak masuk repository.
- [x] Pastikan tidak ada secret dalam variable `VITE_*`.
- [x] Tambahkan rate limiting pada endpoint yang diperlukan.
- [x] Uji seluruh rollback transaksi.
- [x] Uji saldo terhadap akumulasi stock movement.
- [x] Jalankan seluruh test dan lint.

---

## Fase 10 — Persiapan Rilis Versi 1
**Fase 10B — PASS WITH CLEAN AUDIT (READY FOR FASE 10C RELEASE VERIFICATION)**

- [x] Buat database seeder untuk role dan permission (`RoleAndPermissionSeeder`).
- [x] Buat akun administrator awal secara aman (`php artisan app:create-initial-admin`).
- [x] Buat panduan instalasi lokal (`docs/INSTALLATION.md`).
- [x] Buat panduan konfigurasi `.env` (`docs/ENVIRONMENT.md`).
- [x] Buat panduan migration dan seeding (`docs/DATABASE_SETUP.md`).
- [x] Buat prosedur backup dan restore MySQL (`docs/MYSQL_BACKUP_RESTORE.md`).
- [x] Buat panduan penggunaan untuk petugas gudang (`docs/WAREHOUSE_USER_GUIDE.md`).
- [x] Siapkan data release menyerupai penggunaan sebenarnya (`ReleaseVerificationSeeder`).
- [x] Pastikan dokumentasi rilis terstruktur dan terverifikasi (`UAT_VERSION_1.md`, `PERFORMANCE_VERSION_1.md`, `PRD_ACCEPTANCE_VERSION_1.md`, `RELEASE_CHECKLIST_VERSION_1.md`).
- [x] Jalankan pengujian otomatis dan lint (Fase 10B automated quality gate).
- [ ] Verifikasi target response maksimal 2 detik untuk operasi umum (Stage 10C benchmark).
- [ ] Verifikasi seluruh kriteria penerimaan dalam `PRD.md` secara operasional (Stage 10C UAT).
- [ ] Jalankan final release acceptance (Stage 10C release gate).

---

## Definition of Done

Sebuah tugas hanya boleh ditandai selesai jika:

- Implementasi sesuai `PRD.md`.
- Implementasi mengikuti `ARCHITECTURE.md`.
- Tidak melanggar aturan dalam `AGENTS.md`.
- Keputusan penting sudah ditambahkan ke `DECISIONS.md`.
- Tidak menambahkan dependency tanpa persetujuan.
- Authorization dan validasi backend sudah diterapkan.
- Database transaction digunakan jika mengubah stok.
- Kondisi loading, empty, error, dan success sudah ditangani.
- Test untuk happy path dan failure path sudah tersedia.
- Tidak ada test lama yang rusak.
- `php artisan test` berhasil.
- `./vendor/bin/pint` berhasil.
- `npm run lint` berhasil.
- Dokumentasi yang terdampak sudah diperbarui.

---

## Sedang Dikerjakan

- [x] Fase 10B — Release Foundation
- [ ] Fase 10C — Release Verification