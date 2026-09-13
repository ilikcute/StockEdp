# StockEdp — Inventory & Field Allocation Management System

[![Laravel](https://img.shields.io/badge/Laravel-13.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue.svg)](https://php.net)
[![Vue](https://img.shields.io/badge/Vue.js-3.x-emerald.svg)](https://vuejs.org)
[![TailwindCSS](https://img.shields.io/badge/Tailwind-v4.x-38bdf8.svg)](https://tailwindcss.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-orange.svg)](https://mysql.com)

**StockEdp** adalah sistem manajemen inventaris dan alokasi persediaan lapangan operasional (EDP / IT Support / Teknisi). Sistem ini dirancang untuk mengelola siklus hidup aset dan barang persediaan mulai dari penerimaan internal (General Affair), distribusi ke personel lapangan (*mobile stock*), pencatatan alokasi penggantian unit toko (*store asset replacement*), hingga rekonsiliasi dan retur unit rusak/afkir.

---

## 🚀 Fitur Utama

1. **Master Data Toko & Lokasi Multi-Tipe**:
   - Master Toko (*Stores*) terintegrasi dengan audit trail (`created_by`, `updated_by`).
   - Lokasi terbagi menjadi `MAIN_WAREHOUSE` (Gudang Induk), `FIELD_PERSONNEL` (Teknisi Lapangan), dan `DAMAGED_STORAGE` (Gudang Afkir).
2. **Kondisi Persediaan Ganda (*Dual Inventory Condition*)**:
   - Pemisahan saldo fisik secara presisi antara kondisi Baik (`GOOD`) dan Rusak/Afkir (`DEFECTIVE`).
3. **Alokasi Penggantian Unit Toko (*Store Asset Replacement*)**:
   - Pencatatan pemasangan unit baru/bagus dan penarikan unit lama/rusak dari toko dalam 1 transaksi atomik ACID.
   - Dilengkapi shortcut barcode scanner (`[F2]`) dan pencarian cepat combobox cerdas.
4. **Penerimaan Internal General Affair (*GA Inflow*)**:
   - Pencatatan barang masuk dari pengadaan baru maupun hasil servis GA dengan nomor memo/SPB.
5. **Transfer Persediaan & Handshake Lapangan**:
   - Status perpindahan barang: `DRAFT` ➔ `IN_TRANSIT` (Kirim) ➔ `RECEIVED` (Terima).
   - Pengakuan fisik hanya bertambah di saldo teknisi setelah tombol *Terima* ditekan.
6. **Stock Opname & Freeze Lokasi**:
   - Pembekuan transaksi (*lock*) pada lokasi yang sedang opname aktif guna mencegah distorsi stok fisik.
   - Mekanisme persetujuan bertingkat (*Maker-Checker*).
7. **Keamanan Presisi Desimal & Integritas Finansial**:
   - Seluruh kuantitas menggunakan tipe data `DECIMAL(14,4)` di basis data.
   - Komputasi backend menggunakan pustaka `BCMath` tanpa konversi floating-point PHP.
8. **Pelaporan Terdedikasi & Ekspor**:
   - Laporan Saldo Teknisi Lapangan (`/reports/field-balances`).
   - Laporan Histori Kerusakan & Alokasi Toko (`/reports/store-allocations`).
   - Kartu Stok, Rekomendasi Replenishment, dan Ekspor CSV.

---

## 🛠️ Persyaratan Sistem (Prerequisites)

- **PHP**: `^8.3` (disarankan PHP 8.3 atau 8.4) dengan ekstensi wajib:
  - `bcmath`, `pdo_mysql`, `mbstring`, `openssl`, `json`, `tokenizer`, `xml`, `ctype`, `fileinfo`.
- **Database**: MySQL `8.0+` / MariaDB `10.5+` (InnoDB Engine).
- **Node.js**: `v18.x+` (disarankan `v20.x` / `v24.x`) & npm `9.x+`.
- **Composer**: `2.x+`.
- **Web Server**: Laragon / Apache / Nginx.

---

## ⚡ Panduan Instalasi Cepat

### 1. Clone Repositori
```bash
git clone https://github.com/ilikcute/StockEdp.git
cd StockEdp
```

### 2. Instalasi Dependensi Backend & Frontend
```bash
composer install
npm install
```

### 3. Konfigurasi Lingkungan (.env)
```bash
cp .env.example .env
php artisan key:generate
```
Sesuaikan konfigurasi database pada berkas `.env`:
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stockedp
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Eksekusi Migrasi & Seeding Basis Data
```bash
php artisan migrate --seed
```

### 5. Jalankan Server Development
Buka 2 terminal terpisah:
```bash
# Terminal 1: Backend Server
php artisan serve

# Terminal 2: Vite Frontend Dev Server
npm run dev
```
Akses aplikasi melalui peramban pada alamat: `http://localhost:8000`.

---

## 📁 Struktur Codebase

Proyek ini mengadopsi pola **Feature-Driven Architecture**:

```text
StockEdp/
├── app/
│   ├── Features/              # Modul domain independen
│   │   ├── Auth/              # Otentikasi, Pengguna, Roles & Permissions
│   │   ├── Inventory/         # Saldo, Mutasi, Opname, Transfer, Adjustment, Receipt
│   │   ├── Location/          # Gudang Induk, Teknisi Lapangan, Gudang Afkir
│   │   ├── Product/           # Master Produk & Serial Number
│   │   ├── Store/             # Master Data Toko
│   │   ├── StoreAllocation/   # Alokasi Penggantian Unit Toko & Tarik Unit Rusak
│   │   ├── Reporting/         # Modul Laporan Transaksi & Saldo
│   │   └── ...
│   └── Shared/                # Utilitas global, Exception, Base Classes
├── resources/
│   └── js/
│       ├── features/          # UI Halaman, Store Pinia, dan API per fitur
│       ├── shared/            # Komponen dasar (Combobox, Modal, Layout, Navigation)
│       └── router/            # Vue Router dengan guard otorisasi
├── database/
│   ├── migrations/            # Skema tabel terindeks rapi
│   └── seeders/               # Master seeder data awal
└── docs/                      # Dokumentasi teknis & operasional komprehensif
```

---

## 🧪 Pengujian & Standar Kualitas

```bash
# Menjalankan seluruh Unit & Feature Tests
php artisan test

# Menjalankan test modul spesifik
php artisan test tests/Feature/StoreAllocation/StoreAllocationTest.php

# Pemeriksaan Code Style Backend (Laravel Pint)
./vendor/bin/pint --test

# Pemeriksaan Frontend Linter (ESLint)
npm run lint

# Bundling Frontend Production (Vite)
npm run build
```

---

## 📚 Indeks Dokumentasi Teknis

Dokumentasi terperinci tersedia di dalam folder [`docs/`](file:///D:/laragon/www/StockEdp/docs/):
- [PRD.md](file:///D:/laragon/www/StockEdp/docs/PRD.md) — Product Requirement Document V1.
- [INSTALLATION.md](file:///D:/laragon/www/StockEdp/docs/INSTALLATION.md) — Panduan instalasi dan deployment server.
- [WAREHOUSE_USER_GUIDE.md](file:///D:/laragon/www/StockEdp/docs/WAREHOUSE_USER_GUIDE.md) — Panduan pengguna gudang & teknisi.
- [DATABASE_SETUP.md](file:///D:/laragon/www/StockEdp/docs/DATABASE_SETUP.md) — Arsitektur tabel, relasi, dan indexing.
- [OPERATIONAL_DASHBOARD.md](file:///D:/laragon/www/StockEdp/docs/OPERATIONAL_DASHBOARD.md) — Arsitektur dasbor operasional.
- [BARCODE_SCANNER.md](file:///D:/laragon/www/StockEdp/docs/BARCODE_SCANNER.md) — Spesifikasi integrasi barcode scanner HID [F2].
- [REPLENISHMENT_RECOMMENDATIONS.md](file:///D:/laragon/www/StockEdp/docs/REPLENISHMENT_RECOMMENDATIONS.md) — Logika rekomendasi pengisian persediaan.
- [AUDIT_SISTEM_DAN_KODE.md](file:///D:/laragon/www/StockEdp/docs/AUDIT_SISTEM_DAN_KODE.md) — Laporan audit menyeluruh dan rencana remediasi.
- [TASKS.md](file:///D:/laragon/www/StockEdp/docs/TASKS.md) — Catatan riwayat tugas append-only.

---

## 📄 Lisensi
Sistem ini merupakan perangkat lunak berlisensi internal di bawah lisensi yang tertera pada [LICENSE](file:///D:/laragon/www/StockEdp/LICENSE).
