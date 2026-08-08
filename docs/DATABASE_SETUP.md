# Panduan Setup Database, Migration, dan Seeding

Dokumen ini mendokumentasikan tata cara inisialisasi, seeding role/permission, pembuatan administrator awal, serta pemeliharaan skema database pada Sistem Inventory Version 1.

---

## 1. Setup Database Baru (Fresh Installation)

Eksekusi perintah berikut secara berurutan:

```powershell
# 1. Jalankan seluruh migration skema database
php artisan migrate

# 2. Seed role dan permission resmi (Deterministik & Idempotent)
php artisan db:seed --class=RoleAndPermissionSeeder

# 3. Buat akun administrator awal melalui CLI interaktif
php artisan app:create-initial-admin
```

---

## 2. Hak Akses Lokasi Administrator Awal (`CreateInitialAdminCommand`)

- **Cakupan Lokasi Awal**: Administrator awal menerima akses ke seluruh lokasi yang tersedia di database saat command `app:create-initial-admin` dijalankan.
- **Siklus Lokasi Baru**: `NEW_LOCATIONS_REQUIRE_MANUAL_ADMIN_ASSIGNMENT`. Apabila lokasi baru ditambahkan di kemudian hari, pengguna administrator wajib diberikan izin akses lokasi secara manual melalui manajemen akses pengguna, karena Version 1 secara ketat menerapkan scoping lokasi tanpa bypass implisit.

---

## 3. Update Sistem Existing (Update Migration)

Saat memperbarui aplikasi ke versi terbaru, jalankan migration dan resync seeder dengan opsi `--force` pada lingkungan rilis:

```powershell
php artisan migrate --force
php artisan db:seed --class=RoleAndPermissionSeeder --force
```

---

## 4. Catatan Penting Mengenai Migration & Seeding

1. **Perintah `migrate:fresh`**:
   - `php artisan migrate:fresh` akan menghapus seluruh tabel dan data secara permanen.
   - Dilarang menjalankan `migrate:fresh` pada lingkungan operasional/rilis.

2. **Seeder Verification Data (`ReleaseVerificationSeeder`)**:
   - Class `ReleaseVerificationSeeder` dirancang **khusus** untuk pengujian rilis dan benchmark pada lingkungan `local` atau `testing`.
   - Class ini secara otomatis menolak dieksekusi pada lingkungan `production`.
   - Dilarang memasukkan `ReleaseVerificationSeeder` ke dalam `DatabaseSeeder.php` utama.
