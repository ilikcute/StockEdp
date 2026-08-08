# Inventory System

Aplikasi web untuk mencatat, mengendalikan, dan menelusuri persediaan barang berdasarkan produk dan lokasi penyimpanan.

Sistem mendukung penerimaan, pengeluaran, transfer, adjustment, stock opname, saldo persediaan, kartu stok, dan laporan inventory.

## Tujuan

Inventory System dibuat untuk membantu petugas gudang, admin inventory, dan supervisor dalam:

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

Laravel dan MySQL menjadi sumber kebenaran saldo stok. Pinia hanya mengelola state antarmuka dan tidak menentukan hasil akhir transaksi inventory.

## Ruang Lingkup Versi 1

Versi pertama mencakup:

1. Master produk, kategori, satuan, supplier, dan lokasi.
2. Penerimaan dan pengeluaran barang.
3. Transfer stok antarlokasi.
4. Stock adjustment dan stock opname.
5. Saldo stok dan riwayat stock movement.
6. Laporan inventory & ekspor CSV.

Point of Sale, akuntansi, purchasing lengkap, aplikasi mobile, mode offline, cloud deployment, dan sinkronisasi multicabang tidak termasuk versi pertama.

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

Stock movement yang sudah diposting tidak boleh diedit atau dihapus permanen. Kesalahan diperbaiki menggunakan reversal atau adjustment baru.

## Dokumentasi Resmi Rilis Versi 1

| Dokumen | Fungsi |
|---|---|
| [PRD.md](PRD.md) | Ruang lingkup, pengguna, persyaratan, dan kriteria penerimaan |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Struktur folder, aliran data, dan batas dependency |
| [AGENTS.md](AGENTS.md) | Aturan yang wajib diikuti developer dan AI |
| [TASKS.md](TASKS.md) | Urutan pekerjaan dan status implementasi |
| [DECISIONS.md](DECISIONS.md) | Alasan di balik keputusan teknis dan bisnis |
| [docs/INSTALLATION.md](docs/INSTALLATION.md) | Panduan instalasi dan persiapan sistem |
| [docs/ENVIRONMENT.md](docs/ENVIRONMENT.md) | Panduan variabel lingkungan (`.env`) |
| [docs/DATABASE_SETUP.md](docs/DATABASE_SETUP.md) | Panduan migration, seeding, & initial admin command |
| [docs/MYSQL_BACKUP_RESTORE.md](docs/MYSQL_BACKUP_RESTORE.md) | Prosedur backup & restore drill MySQL |
| [docs/WAREHOUSE_USER_GUIDE.md](docs/WAREHOUSE_USER_GUIDE.md) | Panduan operasional pengguna/petugas gudang |
| [docs/UAT_VERSION_1.md](docs/UAT_VERSION_1.md) | Matriks User Acceptance Testing (UAT Version 1) |
| [docs/PERFORMANCE_VERSION_1.md](docs/PERFORMANCE_VERSION_1.md) | Laporan pengujian performa API ($\le 2.000\text{ ms}$) |
| [docs/PRD_ACCEPTANCE_VERSION_1.md](docs/PRD_ACCEPTANCE_VERSION_1.md) | Matriks kriteria penerimaan PRD Version 1 |
| [docs/RELEASE_CHECKLIST_VERSION_1.md](docs/RELEASE_CHECKLIST_VERSION_1.md) | Checklist kesiapan rilis Version 1 |

## Pengujian dan Lint

Jalankan pengujian backend:

```bash
php artisan test
```

Format kode backend:

```bash
./vendor/bin/pint
```

Periksa kode frontend:

```bash
npm run lint
```
