# Aturan Proyek

Baca dan pahami aturan ini sebelum menulis atau mengubah kode.

## Tentang Proyek

Aplikasi web Sistem Inventory untuk mengelola produk, kategori, satuan,
supplier, lokasi penyimpanan, stok masuk, stok keluar, transfer stok,
penyesuaian stok, stock opname, dan laporan persediaan.

## Teknologi

- Backend: Laravel
- Frontend: Vue.js 3
- State Management: Pinia
- Database: MySQL
- Web Server: Local development server
- Komunikasi API: REST API dengan format JSON
- Penyimpanan file: Local Storage Laravel

## Perintah

```bash
# Menjalankan aplikasi
composer run dev

# Menjalankan pengujian backend
php artisan test

# Memformat kode backend
./vendor/bin/pint

# Memeriksa kode frontend
npm run lint