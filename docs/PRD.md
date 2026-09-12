# Product Requirements Document (PRD)
## Inventory & Field Allocation Management System (V1)

**Ringkasan Produk:**  
Aplikasi web operasional persediaan terpadu untuk mencatat pengadaan barang internal dari Dept. General Affair (GA), memantau saldo persediaan di Gudang Induk ADM, mengelola serah-terima stok bergerak (*mobile/van stock*) yang dibawa oleh Tim Operasional/EDP Lapangan, serta menelusuri alokasi penggantian unit/peralatan yang rusak di lokasi toko secara akurat, transparan, dan terintegrasi melalui audit trail mutasi stok, Stock Opname, dan Adjustment.

**Pengguna Target:**  
1. **Admin Inventory / Supervisor**: Mengelola master data, memverifikasi permohonan/alokasi, merekonsiliasi Stock Opname, dan melakukan audit sistem.  
2. **Petugas Gudang Induk ADM**: Menangani penerimaan fisik dari GA, penyiapan barang (*dispatch*), retur barang rusak dari lapangan, dan opname gudang.  
3. **Teknisi / EDP Operasional Lapangan**: Menerima transferan stok dari gudang induk, membawa stok operasional, mencatat alokasi penggantian unit rusak di toko secara *mobile*, dan meretur unit afkir ke gudang induk.

**Indikator Keberhasilan:**  
- Pengguna lapangan dapat menyelesaikan pencatatan alokasi penggantian unit di toko dalam waktu **kurang dari 45 detik**.  
- 100% mutasi stok (fisik dan transit) tercatat secara terpusat (*zero untracked inventory*).  
- Tidak ada selisih tanpa pertanggungjawaban antara barang yang dikirim gudang dengan yang diterima tim operasional (*in-transit reconciliation*).  
- Riwayat penggantian unit di setiap toko dapat ditelusuri riwayatnya (*store-level traceability*).

---

## 1. Ruang Lingkup Versi 1 (Scope)

### Fitur Termasuk
- **Master Data Multiguna:** Produk, kategori, satuan, spesifikasi/serial number (opsional), daftar toko tujuan (Kode & Nama Toko), serta entitas Lokasi Penyimpanan multi-tipe.
- **Penerimaan Barang Internal GA:** Penerimaan stok baru/servis ke Gudang Induk ADM berbasis dokumen internal SPB (Surat Permintaan Barang) / Memo GA.
- **Manajemen Lokasi Dinamis:** Pemisahan antara Gudang Induk Fisik (*Main Warehouse*), Stok Operasional Petugas/EDP (*Mobile Stock*), dan Gudang Isolasi Afkir/Rusak (*Damaged/RMA Storage*).
- **Kondisi Persediaan (Dual Inventory Condition):** Dukungan pemisahan stok berkondisi **Baik (*Good Condition*)** dan **Rusak (*Defective/Damaged Condition*)** pada setiap lokasi.
- **Transfer Stok 2 Tahap (Handshake In-Transit):**
  - Tahap 1: Pengiriman (*Dispatch*) mengurangi stok asal, mencatat status `IN_TRANSIT`.
  - Tahap 2: Penerimaan (*Receipt Confirmation*) oleh teknisi/penerima menambah saldo aktif di lokasi tujuan, atau pelaporan selisih kirim.
- **Alokasi Penggantian Unit Toko (*Store Asset Replacement*):** Pencatatan langsung oleh EDP lapangan saat memasang sparepart/peralatan di toko, memotong saldo teknisi bersangkutan, dan mendata unit bekas yang dicopot.
- **Retur Barang Lapangan ke Gudang Induk:** Pengembalian stok sisa atau unit rusak dari teknisi ke gudang induk.
- **Stock Opname & Rekonsiliasi:** Pemeriksaan fisik berkala di gudang induk maupun pada tas/kendaraan teknisi lapangan, disusul rekonsiliasi selisih.
- **Stock Adjustment Terkontrol:** Penyesuaian stok sistem dengan otorisasi supervisor/admin berbasis alasan yang valid.
- **Laporan & Audit Log:** Kartu stok (*stock ledger*), laporan saldo per lokasi/teknisi, riwayat penggantian unit per toko, laporan in-transit, dan ekspor data (Excel/CSV/PDF).

---

## 2. Di Luar Ruang Lingkup (Out of Scope V1)

- Modul POS (Point of Sale), transaksi penjualan retail, kasir, atau kas keluar-masuk.
- Jurnal akuntansi umum, neraca keuangan, hutang-piutang (*double-entry bookkeeping*).
- Modul ticketing helpdesk/troubleshooting (aplikasi berfokus pada administrasi logistik/fisik barangnya).
- Sinkronisasi data offline berbasis *service worker* (aplikasi mewajibkan koneksi intranet/internet stabil saat men-submit transaksi).
- Aplikasi native mobile Android/iOS (menggunakan Web App responsif berbasis PWA/Mobile View).
- Integrasi otomatis ke ERP pusat atau marketplace eksternal via webhook/third-party API.

---

## 3. Batasan & Arsitektur Teknis

- **Arsitektur:** Monolith decouple / Single Page Application (SPA).
- **Backend:** Laravel (Fitur-first module, Controller ramping, Domain Service untuk business logic stok, Form Request validation, Eloquent ORM).
- **Frontend:** Vue.js 3 (Composition API, `<script setup>`), didukung Vite dan Tailwind CSS untuk UI responsif (Desktop & Tablet/Smartphone lapangan).
- **State Management:** Pinia (hanya untuk global UI state, session auth, dan caching referensi dasar; dilarang menghitung saldo stok di client).
- **Database:** MySQL (InnoDB Engine, wajib ACID compliance via `DB::transaction()` dan locking row `lockForUpdate()`).
- **Komunikasi:** REST API JSON dengan format response seragam (`success`, `message`, `data`, `errors`) dan prefix `/api/v1`.
- **Autentikasi & Otorisasi:** Laravel Sanctum (Token-based atau Stateful Cookie) dilengkapi Role-Based Access Control (RBAC) granular.
- **Penyimpanan Berkas:** Local disk storage untuk lampiran berkas SPB/foto kerusakan unit.
- **Konfigurasi Lingkungan:** Zona Waktu `Asia/Jakarta`, Bahasa Antarmuka: Bahasa Indonesia, Format Angka/Desimal: Standard 2 digit precision (`DECIMAL(12,2)`).

---

## 4. Sasaran Produk & Integritas Data

### 4.1. Konsistensi Mutasi Stok (Immutable Movement)
- Saldo stok tidak boleh di-*hard update* secara sembarangan. Setiap penambahan, pengurangan, dan perpindahan **wajib** mencatat entri baru ke tabel `stock_movements`.
- Sekali tercatat, data mutasi **tidak boleh diubah (UPDATE) atau dihapus (DELETE)**. Kesalahan transaksi wajib diselesaikan via *reversal* atau penyesuaian (*adjustment*).

### 4.2. Perlindungan Stok Negatif & Concurrency Control
- Nilai saldo stok tidak boleh bernilai minus (`quantity >= 0`).
- Setiap transaksi mutasi wajib membungkus proses kalkulasi dalam `DB::transaction()` serta mengunci baris data saldo menggunakan `lockForUpdate()` guna menghindari insiden *race condition* saat diakses teknisi dan admin secara paralel.

### 4.3. Ketertelusuran Komprehensif (Audit Trail)
Setiap mutasi stok harus merekam data minimum:
- ID Produk & Serial Number (jika terdata).
- Lokasi asal & tujuan (termasuk identitas teknisi pemegang stok).
- Kondisi fisik barang (`GOOD` atau `DEFECTIVE`).
- Tipe mutasi (`RECEIPT_GA`, `TRANSFER_OUT`, `TRANSFER_IN`, `STORE_ALLOCATION`, `REPLACEMENT_PULL`, `RETURN_TO_WAREHOUSE`, `ADJUSTMENT_IN`, `ADJUSTMENT_OUT`, `OPNAME_RECONCILE`).
- Quantity perubahan, Quantity sebelum, dan Quantity sesudah.
- Nomor referensi unik (Nomor SPB, Nomor Transfer, Nomor Alokasi Toko, dll).
- ID Pengguna pelaksana dan Timestamp mutasi.

---

## 5. Peran & Hak Akses Pengguna

| Peran (Role) | Tanggung Jawab Operasional | Hak Akses Utama |
| :--- | :--- | :--- |
| **Administrator** | Pemeliharaan sistem & pengawasan menyeluruh | Akses penuh master data, manajemen akun pengguna, bypass pembatalan berizin khusus, konfigurasi parameter sistem, seluruh audit log. |
| **Supervisor Inventory** | Pengawasan kepatuhan & rekonsiliasi fisik | Menyetujui Stock Adjustment, menutup sesi Stock Opname, memverifikasi selisih transfer *in-transit*, melihat seluruh laporan pergerakan stok toko. |
| **Petugas Gudang Induk** | Eksekusi fisik di Gudang Induk ADM | Input penerimaan barang dari GA, membuat transfer *dispatch* ke teknisi, konfirmasi retur barang masuk dari lapangan, opname internal gudang. |
| **Teknisi / EDP Lapangan** | Eksekusi operasional di toko-toko | Melihat stok pada lokasi pribadinya (*mobile stock*), konfirmasi terima transfer dari gudang, mencatat alokasi penggantian unit di toko, membuat transfer retur barang rusak ke gudang. |

---

## 6. Persyaratan Fungsional Detail

### 6.1. Master Data & Pengelolaan Lokasi
- **Master Produk & Satuan:** Pengelolaan SKU unik, Barcode, Nama Produk, Kategori, Satuan (Pcs, Box, Set), dan Batas Stok Minimum di Gudang Induk.
- **Daftar Toko Target:** Pendataan master toko (Kode Toko, Nama Toko, Wilayah/Area, Alamat Singkat) sebagai referensi cepat saat alokasi lapangan.
- **Master Lokasi Multi-Tipe:**
  - `MAIN_WAREHOUSE`: Gudang sentral ADM tempat barang pertama kali diterima dari GA.
  - `FIELD_PERSONNEL`: Lokasi representasi personal teknisi/EDP (terikat langsung dengan `user_id` teknisi).
  - `DAMAGED_STORAGE`: Tempat karantina unit afkir/rusak sebelum pemusnahan atau klaim garansi GA.

### 6.2. Penerimaan Stok dari General Affair (GA Inflow)
- Form input penerimaan khusus Gudang Induk dengan atribut:
  - Nomor Referensi Penerimaan (Auto-generated).
  - Nomor Memo / SPB Internal Dept. GA.
  - Tanggal Penerimaan Fisik.
  - Pilihan Sumber: `GA_PROCUREMENT` (Barang Baru dari Pengadaan) atau `GA_SERVICED` (Barang Kembali dari Servis Eksternal).
  - Daftar item barang, quantity, dan status kondisi (Default: `GOOD`).
  - Unggah foto nota/surat jalan penerimaan (opsional).
- Menghasilkan movement: `RECEIPT_GA`.

### 6.3. Transfer Stok 2 Tahap (Gudang Induk ke Lapangan & Sebaliknya)
- **Fase Pengiriman (Dispatch):**
  - Pembuat dokumen memilih lokasi asal dan lokasi tujuan (misal: Gudang Induk -> EDP Lapangan A).
  - Sistem memeriksa ketersediaan stok fisik di lokasi asal.
  - Mengunci dan mengurangi stok lokasi asal, memindahkan status barang menjadi `IN_TRANSIT`.
  - Terbit Surat Jalan Transfer elektronik (*Dispatch Note*).
- **Fase Konfirmasi Penerimaan (Handshake):**
  - Pengguna di lokasi tujuan (teknisi via perangkatnya) menerima notifikasi/daftar item `IN_TRANSIT` yang ditujukan kepadanya.
  - Teknisi memeriksa kesesuaian fisik dan menekan tombol konfirmasi terima (`Confirm Received`).
  - Sistem memindahkan saldo dari `IN_TRANSIT` menjadi saldo aktif lokasi penerima.
  - Jika terdapat selisih/kerusakan saat pengiriman, teknisi dapat mencatat *discrepancy* yang otomatis memicu review supervisor.

### 6.4. Alokasi Penggantian Unit Toko (Store Asset Replacement)
- Modul utama untuk teknisi operasional saat di lokasi toko:
  - Form ringkas: Pilih Toko (Dropdown pencarian cepat via Kode/Nama Toko).
  - Pilih Barang Pasang: Mengambil dari stok `GOOD` yang sedang berada di tangan teknisi.
  - Input Barang Tarik/Bongkar (opsional tapi disarankan): Nama/Tipe barang rusak yang dicopot dari toko, alasan kerusakan (mati total, terbakar, error).
  - Keterangan pekerjaan / Catatan teknisi.
- Dampak Sistem:
  - Saldo barang kondisi `GOOD` pada lokasi teknisi terpotong (Movement: `ALLOCATION_OUT`).
  - Jika ada unit bekas yang ditarik, sistem otomatis menambahkan stok tersebut ke lokasi teknisi dengan kondisi `DEFECTIVE` (Movement: `REPLACEMENT_PULL`).
  - Data historis pemakaian sparepart toko langsung ter-update.

### 6.5. Retur Barang Rusak/Sisa ke Gudang Induk
- Teknisi menyerahkan kembali barang (baik unit sisa pasang atau unit rusak hasil tarikan toko) ke Gudang Induk.
- Menggunakan alur transfer 2 tahap dengan tujuan lokasi Gudang Induk atau Gudang Afkir.
- Petugas gudang melakukan verifikasi fisik sebelum menerima barang ke sistem.

### 6.6. Stock Opname & Penyesuaian (Adjustment)
- **Stock Opname Terjadwal:**
  - Sesi opname dapat dibuat per lokasi spesifik (misal: Sesi Opname Tas Teknisi Budi atau Sesi Opname Rak Gudang Induk).
  - Fitur *freeze/warning* transaksi pada lokasi yang sedang aktif dihitung.
  - Input perhitungan fisik (*blind count* atau komparasi langsung sesuai wewenang).
  - Sistem menampilkan selisih (*variance*) antara fisik vs sistem.
- **Stock Adjustment:**
  - Selisih hasil opname tidak langsung menimpa angka saldo secara sepihak.
  - Supervisor memeriksa alasan selisih (hilang, rusak tidak terdata, kesalahan hitung awal).
  - Approval supervisor mengeksekusi rekonsiliasi otomatis menghasilkan movement `ADJUSTMENT_IN` atau `ADJUSTMENT_OUT`.

### 6.7. Pelaporan & Analitik
- **Kartu Stok:** Rekap alur keluar-masuk per produk, per lokasi, dan per periode tanggal.
- **Laporan Saldo Persediaan Lapangan:** Monitoring *real-time* aset yang sedang dipegang oleh masing-masing teknisi lapangan.
- **Histori Kerusakan & Alokasi per Toko:** Analisis frekuensi pergantian peralatan di toko tertentu (memetakan toko mana yang sering mengalami kerusakan hardware).
- **Laporan Barang In-Transit:** Memantau barang yang sedang berada di jalan dan belum dikonfirmasi penerima.
- **Fitur Ekspor:** Tersedia format Microsoft Excel (.xlsx) dan PDF siap cetak.

---

## 7. Persyaratan Nonfungsional (NFR)

- **Keandalan Transaksi:** Menerapkan database transaction atomicity. Kegagalan pada salah satu proses (misal: gagal tulis movement) wajib me-rollback seluruh perubahan saldo terkait secara otomatis.
- **Kinerja Respons:**
  - Endpoint transaksi alokasi toko dan transfer maksimal memproses respons dalam 1,5 detik pada jaringan lokal/intranet.
  - Listing data tabel menggunakan Laravel *server-side pagination* dengan batas aman query (misal: limit 20–50 record per page) untuk mencegah lonjakan memori.
- **Pengalaman Pengguna Lapangan (Mobile Usability):**
  - Antarmuka form alokasi toko dan transfer penerimaan didesain ramah sentuhan layar tablet/smartphone (*mobile-first layout*).
  - Tombol aksi utama dilengkapi status *loading indicator* dan perlindungan *double-click prevention* (debounce) agar tidak terjadi multi-post data yang sama.
- **Standar Kode & Pemeliharaan:**
  - Backend: PSR-12, clean architecture via Services & Action Classes, pengujian unit/feature via PHPUnit (`php artisan test`).
  - Frontend: Vue SFC (Single File Components) dengan validasi form ketat dan feedback notifikasi Toast yang jelas.

---

## 8. Skema Inti Basis Data (MySQL Schema Outline)

```sql
-- 1. Lokasi Penyimpanan
CREATE TABLE locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    type ENUM('MAIN_WAREHOUSE', 'FIELD_PERSONNEL', 'DAMAGED_STORAGE') NOT NULL,
    user_id BIGINT UNSIGNED NULL, -- Terisi jika type = FIELD_PERSONNEL
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_locations_type (type)
);

-- 2. Master Toko
CREATE TABLE stores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    address TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 3. Saldo Stok Terkini
CREATE TABLE product_stocks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    condition ENUM('GOOD', 'DEFECTIVE') NOT NULL DEFAULT 'GOOD',
    quantity DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uk_prod_loc_cond (product_id, location_id, `condition`),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (location_id) REFERENCES locations(id)
);

-- 4. Transfer Header (Mendukung In-Transit 2-Tahap)
CREATE TABLE stock_transfers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transfer_number VARCHAR(60) NOT NULL UNIQUE,
    source_location_id BIGINT UNSIGNED NOT NULL,
    destination_location_id BIGINT UNSIGNED NOT NULL,
    status ENUM('PENDING', 'IN_TRANSIT', 'COMPLETED', 'DISCREPANCY', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
    created_by BIGINT UNSIGNED NOT NULL,
    received_by BIGINT UNSIGNED NULL,
    transferred_at TIMESTAMP NULL,
    received_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (source_location_id) REFERENCES locations(id),
    FOREIGN KEY (destination_location_id) REFERENCES locations(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (received_by) REFERENCES users(id)
);

-- 5. Detail Alokasi Penggantian Toko
CREATE TABLE store_allocations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    allocation_number VARCHAR(60) NOT NULL UNIQUE,
    technician_user_id BIGINT UNSIGNED NOT NULL,
    technician_location_id BIGINT UNSIGNED NOT NULL,
    store_id BIGINT UNSIGNED NOT NULL,
    allocated_at DATE NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (technician_user_id) REFERENCES users(id),
    FOREIGN KEY (technician_location_id) REFERENCES locations(id),
    FOREIGN KEY (store_id) REFERENCES stores(id)
);

-- 6. Log Mutasi Riwayat Stok (Immutable Ledger)
CREATE TABLE stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    condition ENUM('GOOD', 'DEFECTIVE') NOT NULL DEFAULT 'GOOD',
    movement_type ENUM(
        'RECEIPT_GA', 
        'TRANSFER_OUT', 
        'TRANSFER_IN', 
        'STORE_ALLOCATION', 
        'REPLACEMENT_PULL', 
        'RETURN_TO_WAREHOUSE', 
        'ADJUSTMENT_IN', 
        'ADJUSTMENT_OUT', 
        'OPNAME_RECONCILE'
    ) NOT NULL,
    quantity_change DECIMAL(12, 2) NOT NULL, -- Positif untuk masuk, Negatif untuk keluar
    quantity_before DECIMAL(12, 2) NOT NULL,
    quantity_after DECIMAL(12, 2) NOT NULL,
    reference_type VARCHAR(100) NOT NULL,    -- Model class atau nama modul referensi
    reference_id BIGINT UNSIGNED NOT NULL,   -- ID header transaksi terkait
    user_id BIGINT UNSIGNED NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL,
    INDEX idx_movement_lookup (product_id, location_id, created_at),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

## 9. Aturan Bisnis & Ketentuan Tetap (Non-Negotiable Rules)
- ** Prinsip Kebenaran Tunggal: Database MySQL pada server backend adalah satu-satunya sumber kebenaran status dan saldo stok. Antarmuka Vue.js dilarang memanipulasi atau mengasumsikan kalkulasi saldo final.

- ** Keterikatan Akun Teknisi: Pengguna dengan peran teknisi operasional hanya dapat menginput alokasi atau transfer keluar dari lokasi stok miliknya sendiri.

- ** Pemisahan Kondisi Fisik: Barang kondisi rusak (DEFECTIVE) tidak dapat ditransfer sebagai alokasi penggantian unit toko.

- ** Verifikasi Penerimaan GA: Penerimaan stok baru di gudang induk wajib menyertakan identitas referensi dokumen SPB/memo GA untuk mencegah penerimaan fiktif.

- ** Kepatuhan Larangan Stok Negatif: Sistem backend akan secara otomatis menolak dan menggagalkan transaksi (melempar ValidationException / DomainException) apabila mutasi yang diminta menyebabkan saldo suatu barang bernilai di bawah 0.00.

## 10. Kriteria Penerimaan Sistem (Acceptance Criteria V1)
[x] Petugas Gudang Induk berhasil mencatat penerimaan barang baru berdasarkan nomor memo GA, dan saldo bertambah dengan kondisi GOOD.

[x] Petugas Gudang Induk berhasil membuat transfer keluar ke teknisi A, status barang menjadi IN_TRANSIT, dan belum menambah saldo teknisi A sampai dikonfirmasi.

[x] Teknisi A dapat melihat daftar barang yang dikirim kepadanya dan menekan tombol terima, mengubah saldo aktif di lokasi teknisi A.

[x] Teknisi A dapat mencatat penggantian suku cadang di Toko B, saldo GOOD miliknya berkurang, dan sparepart lama yang ditarik tercatat sebagai stok DEFECTIVE miliknya.

[x] Teknisi A dapat mengembalikan unit DEFECTIVE tersebut ke Gudang Afkir/Gudang Induk melalui modul retur transfer.

[x] Supervisor dapat melakukan stock opname acak terhadap stok yang dibawa teknisi, membandingkan fisik vs sistem, dan membuat adjustment jika disetujui.

[x] Setiap histori pemakaian unit per toko dapat ditarik laporannya berdasarkan filter nama/kode toko dan rentang tanggal.

[x] Pengujian otomatis backend (php artisan test) lolos tanpa kegagalan integritas mutasi.