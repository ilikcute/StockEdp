# Panduan Instalasi Sistem Inventory (Version 1)

Dokumen ini berisi panduan lengkap untuk melakukan instalasi dan penyetelan Sistem Inventory pada lingkungan lokal atau jaringan lokal.

---

## 1. Persyaratan Sistem (System Requirements)

- **PHP**: `8.3+` (disarankan PHP 8.3 atau 8.4) dengan ekstensi wajib:
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

---

## 4. Menjalankan Test Suite

Suite test memakai database MySQL terpisah `stockedp_test` (lihat `phpunit.xml`).

> **PENTING — Cara aman menjalankan test:**
> 1. Hanya jalankan **satu** runner `php artisan test` pada satu waktu terhadap `stockedp_test`.
>    Menjalankan dua runner secara paralel pada database yang sama akan saling me-robolob skema
>    (menyebabkan error `1412`, `1615`, `deadlock 1213`, atau pesan "Table doesn't exist").
> 2. Pastikan skema test bersih setiap kali memulai run (RefreshDatabase melakukannya otomatis).

```powershell
# Run penuh (default; kendala regresi — grup "benchmark" dikecualikan otomatis)
php artisan test

# Run penuh + benchmark performa (SLA waktu eksekusi)
php artisan test --group=benchmark

# Tanpa benchmark secara eksplisit
php artisan test --exclude-group=benchmark
```

Test yang peka terhadap environment (SLA waktu, batas jumlah query, dataset integritas besar)
dikategorikan sebagai grup `benchmark` agar tidak menyebabkan kegagalan flaky pada run regresi biasa.

---

## 5. Deployment Produksi (Checklist)

Sebelum go-live, pastikan:

### 5.1 Environment (`docs/ENVIRONMENT.md`)
- `APP_ENV=production`, `APP_DEBUG=false` (wajib — jika `true`, detail error internal bocor ke pengguna).
- `APP_URL` memakai domain publik + HTTPS.
- `SESSION_SECURE_COOKIE=true` (default aktif otomatis saat `APP_ENV=production`, lihat `config/session.php`).
- `CORS_ALLOWED_ORIGINS` dan `SANCTUM_STATEFUL_DOMAINS` dipersempit hanya ke domain frontend publik.
- `LOG_LEVEL=info` (jangan `debug`).
- Untuk skala lebih besar: `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis` (opsional; default `database` masih aman).

### 5.2 Proses latar yang harus berjalan
- **Queue worker**: `php artisan queue:work` (di-supervisi systemd/Supervisor).
- **Scheduler**: `php artisan schedule:work` (atau cron `* * * * * php /path/artisan schedule:run`).
  Job terjadwal saat ini: pembersihan token Sanctum kedaluwarsa & failed queue harian.
- **Reverb (real-time)**: `php artisan reverb:start` bila fitur realtime dipakai.

### 5.3 Tata urutan deploy
```powershell
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```
