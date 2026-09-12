# Panduan Instalasi Sistem Inventory (Version 1)

Dokumen ini berisi panduan lengkap untuk melakukan instalasi dan penyetelan Sistem Inventory pada lingkungan lokal atau jaringan lokal.

---

## 1. Persyaratan Sistem (System Requirements)

- **PHP**: `8.5.0` (atau minimal `8.2+`) dengan ekstensi wajib:
  - `bcmath` (Wajib untuk perhitungan desimal kuantitas presisi)
  - `pdo_mysql`
  - `mbstring`
  - `openssl`
  - `json`
  - `tokenizer`
  - `xml`
  - `ctype`
  - `fileinfo`
- **Composer**: `2.x+`
- **Node.js**: `v24.x` (atau minimal `18.x+`)
- **npm**: `11.x` (atau minimal `9.x+`)
- **Database**: MySQL `8.0+`
- **Web Server / Dev Environment**: Laragon / Apache / Nginx / Local Development Server

---

## 2. Langkah-Langkah Instalasi

### Langkah 1: Clone Repository dan Masuk Directory

```powershell
git clone https://github.com/ilikcute/StockEdp.git
cd StockEdp
```

### Langkah 2: Install Backend Dependencies

```powershell
composer install
```

### Langkah 3: Install Frontend Dependencies

```powershell
npm ci
```

### Langkah 4: Konfigurasi Environment File

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Sesuaikan parameter koneksi database pada `.env`:
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stockedp
DB_USERNAME=root
DB_PASSWORD=
```

### Langkah 5: Jalankan Migration Database dan Role Seeder

```powershell
php artisan migrate
php artisan db:seed --class=RoleAndPermissionSeeder
```

### Langkah 6: Buat Akun Administrator Awal

Jalankan perintah interaktif untuk membuat akun administrator awal:

```powershell
php artisan app:create-initial-admin
```

Masukkan identitas (`Nama`, `Username`, `Email`, `Password`, `Konfirmasi Password`).

*Catatan Hak Akses Lokasi*: Administrator awal menerima akses ke seluruh lokasi yang tersedia di database saat command dieksekusi. Lokasi baru yang ditambahkan di kemudian hari memerlukan penugasan manual (`NEW_LOCATIONS_REQUIRE_MANUAL_ADMIN_ASSIGNMENT`).

### Langkah 7: Build Assets Frontend

```powershell
npm run build
```

### Langkah 8: Bersihkan dan Optimalkan Cache

```powershell
php artisan optimize:clear
php artisan optimize
```

---

## 3. Menjalankan Aplikasi

Jalankan server pengembangan backend dan frontend secara bersamaan:

```powershell
composer run dev
```

Aplikasi web dapat diakses di browser melalui URL: `http://localhost:8000` (atau sesuai konfigurasi `APP_URL`).
