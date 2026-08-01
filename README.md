<<<<<<< HEAD
# StockEdp
Program pencatatan stock barang
=======
# Inventory System

Aplikasi web untuk mencatat, mengendalikan, dan menelusuri persediaan barang
berdasarkan produk dan lokasi penyimpanan.

Sistem mendukung penerimaan, pengeluaran, transfer, adjustment, stock opname,
saldo persediaan, kartu stok, dan laporan inventory.

## Tujuan

Inventory System dibuat untuk membantu petugas gudang, admin inventory, dan
supervisor dalam:

- Mengetahui saldo stok terkini pada setiap lokasi.
- Mencatat seluruh perubahan stok secara konsisten.
- Mencegah pengeluaran melebihi stok tersedia.
- Menelusuri perubahan stok ke transaksi asal.
- Membandingkan stok sistem dengan hasil perhitungan fisik.
- Menghasilkan laporan inventory yang dapat dipercaya.

## Teknologi

| Bagian | Teknologi |
|---|---|
| Backend | Laravel |
| Frontend | Vue.js 3 |
| State management | Pinia |
| Database | MySQL |
| API | REST API JSON |
| File storage | Laravel local storage |
| Web server | Local development server |
| Realtime | Belum digunakan pada versi awal |

Laravel dan MySQL menjadi sumber kebenaran saldo stok. Pinia hanya mengelola
state antarmuka dan tidak menentukan hasil akhir transaksi inventory.

## Ruang Lingkup Versi 1

Versi pertama mencakup:

1. Master produk, kategori, satuan, supplier, dan lokasi.
2. Penerimaan dan pengeluaran barang.
3. Transfer stok antarlokasi.
4. Stock adjustment dan stock opname.
5. Saldo stok dan riwayat stock movement.
6. Laporan inventory.

Point of Sale, akuntansi, purchasing lengkap, aplikasi mobile, mode offline,
cloud deployment, dan sinkronisasi multicabang tidak termasuk versi pertama.

Lihat [PRD.md](PRD.md) untuk ruang lingkup dan kriteria penerimaan lengkap.

## Prinsip Inventory

Setiap perubahan stok wajib menghasilkan stock movement.

```text
Transaksi
    ↓
Validasi dan authorization
    ↓
Database transaction
    ├── Lock saldo stok
    ├── Validasi ketersediaan
    ├── Simpan stock movement
    ├── Perbarui saldo stok
    └── Simpan audit trail
```

Jika salah satu proses gagal, seluruh perubahan harus dibatalkan.

Stock movement yang sudah diposting tidak boleh diedit atau dihapus permanen.
Kesalahan diperbaiki menggunakan reversal atau adjustment baru.

## Struktur Proyek

```text
project-root/
├── app/
│   ├── Features/
│   │   ├── Auth/
│   │   ├── Product/
│   │   ├── Category/
│   │   ├── Unit/
│   │   ├── Supplier/
│   │   ├── Warehouse/
│   │   ├── Inventory/
│   │   ├── StockMovement/
│   │   ├── StockAdjustment/
│   │   ├── StockOpname/
│   │   └── Reporting/
│   └── Shared/
│
├── resources/js/
│   ├── features/
│   ├── shared/
│   ├── router/
│   ├── App.vue
│   └── main.js
│
├── database/
├── routes/
├── tests/
├── AGENTS.md
├── ARCHITECTURE.md
├── DECISIONS.md
├── PRD.md
├── TASKS.md
└── .env.example
```

Struktur proyek menggunakan pendekatan feature-first. Kode hanya ditempatkan
di `Shared` jika digunakan oleh minimal dua fitur.

Lihat [ARCHITECTURE.md](ARCHITECTURE.md) untuk aturan layer, dependency, dan
aliran data.

## Persyaratan Sistem

Pastikan perangkat lokal memiliki:

- PHP sesuai kebutuhan versi Laravel.
- Composer.
- Node.js dan npm.
- MySQL.
- Ekstensi PHP yang dibutuhkan Laravel.

Versi pasti setiap runtime mengikuti `composer.json` dan `package.json`.

## Instalasi

### 1. Ambil source code

```bash
git clone <repository-url>
cd <project-directory>
```

### 2. Instal dependency backend

```bash
composer install
```

### 3. Instal dependency frontend

```bash
npm install
```

### 4. Buat konfigurasi lokal

```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan konfigurasi MySQL dalam `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_system
DB_USERNAME=root
DB_PASSWORD=
```

Jangan commit file `.env` atau memasukkan secret ke variable dengan prefix
`VITE_`.

### 5. Buat database

Buat database MySQL:

```sql
CREATE DATABASE inventory_system
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

### 6. Jalankan migration

```bash
php artisan migrate
```

Jika seeder awal sudah tersedia:

```bash
php artisan db:seed
```

Jangan menjalankan `migrate:fresh` pada database yang berisi data penting
karena perintah tersebut menghapus seluruh tabel.

### 7. Jalankan aplikasi

```bash
composer run dev
```

Secara umum aplikasi dapat diakses melalui:

```text
Frontend: http://localhost:5173
Backend:  http://localhost:8000
API:      http://localhost:8000/api/v1
```

Alamat sebenarnya mengikuti output terminal dan konfigurasi `.env`.

## Pengujian

Jalankan pengujian backend:

```bash
php artisan test
```

Pengujian wajib mencakup:

- Authentication dan authorization.
- Validasi transaksi.
- Pencegahan stok negatif.
- Database transaction dan rollback.
- Konsistensi saldo dengan stock movement.
- Transaksi bersamaan pada saldo yang sama.

## Format dan Lint

Format kode backend:

```bash
./vendor/bin/pint
```

Periksa kode frontend:

```bash
npm run lint
```

Seluruh test dan lint harus berhasil sebelum tugas dinyatakan selesai.

## Environment Variable

Gunakan `.env.example` sebagai referensi konfigurasi:

```bash
cp .env.example .env
```

Aturan keamanan:

- Jangan commit `.env`.
- Jangan menaruh password atau API key asli di `.env.example`.
- Jangan menaruh secret dalam variable `VITE_*`.
- Jangan menampilkan isi `.env` di log, dokumentasi, atau laporan error.

## Dokumentasi Proyek

| Dokumen | Fungsi |
|---|---|
| [PRD.md](PRD.md) | Ruang lingkup, pengguna, persyaratan, dan kriteria penerimaan |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Struktur folder, aliran data, dan batas dependency |
| [AGENTS.md](AGENTS.md) | Aturan yang wajib diikuti developer dan AI |
| [TASKS.md](TASKS.md) | Urutan pekerjaan dan status implementasi |
| [DECISIONS.md](DECISIONS.md) | Alasan di balik keputusan teknis dan bisnis |
| [.env.example](.env.example) | Daftar environment variable tanpa secret |

Sebelum mengubah kode, baca dokumen dengan urutan:

1. `AGENTS.md`
2. `PRD.md`
3. `ARCHITECTURE.md`
4. `DECISIONS.md`
5. `TASKS.md`

Jika implementasi berbeda dari dokumentasi, hentikan perubahan dan tentukan
apakah kode atau dokumentasi yang harus diperbarui.

## Alur Pengerjaan

1. Pilih satu tugas dari `TASKS.md`.
2. Pastikan kebutuhan tidak memiliki pertanyaan terbuka.
3. Pelajari file referensi atau gold-standard file terkait.
4. Implementasikan perubahan dalam batas fitur.
5. Tambahkan atau perbarui test.
6. Jalankan test, format, dan lint.
7. Perbarui dokumentasi yang terdampak.
8. Tandai tugas selesai di `TASKS.md`.

Jangan melakukan refactor di luar ruang lingkup tugas dan jangan menambahkan
dependency baru tanpa persetujuan.

## API

Seluruh endpoint API menggunakan prefix:

```text
/api/v1
```

Response sukses dan error harus menggunakan struktur yang konsisten. Backend
bertanggung jawab atas:

- Validasi input.
- Authentication dan authorization.
- Business logic.
- Integritas transaksi.
- Perhitungan dan validasi stok.

Frontend tidak boleh menganggap transaksi berhasil sebelum menerima response
sukses dari backend.

## Status Proyek

Status saat ini: **Perencanaan dan penetapan keputusan awal**.

Sebelum implementasi dimulai, selesaikan pertanyaan pada Fase 0 di `TASKS.md`,
terutama:

- Akses pengguna terhadap lokasi.
- Alur status transfer stok.
- Konversi satuan.
- Approval transaksi.
- Metode penilaian persediaan.
- Mekanisme stock opname.
- Metode autentikasi.
- Format ekspor laporan.

## Lisensi

Lisensi proyek belum ditentukan. Jangan mendistribusikan source code ke pihak
lain sebelum kebijakan lisensi ditetapkan.
>>>>>>> 0c5e824 (Commit pertama: Upload proyek)
