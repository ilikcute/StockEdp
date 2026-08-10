# StockEdp — Database Context

## 1. Database yang Digunakan

Database operasional/release Version 1:

```text
stockedp
```

Database verifikasi/rehearsal, BUKAN production:

```text
stockedp_release_uat
stockedp_release_rehearsal
stockedp_release_restore_test
```

Fungsi:

- `stockedp`: database aplikasi utama setelah release/deployment.
- `stockedp_release_uat`: UAT, performance benchmark, concurrency/reconciliation, production-like rehearsal.
- `stockedp_release_rehearsal`: sumber recoverability/backup rehearsal Fase 10C-1.
- `stockedp_release_restore_test`: target restore verification.

## 2. Konfigurasi Production/Release

Target `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stockedp
DB_USERNAME=<DB_USER>
DB_PASSWORD=<DB_PASSWORD>
```

Catatan: repository pernah memiliki inkonsistensi `.env.example` yang memakai `DB_DATABASE=InventorySystem` sementara dokumentasi release memakai `stockedp`. Selalu verifikasi nilai aktual `.env` dan hasil config Laravel.

Cek database yang sedang dibaca Laravel:

```bash
php artisan optimize:clear
php artisan tinker --execute="dump(config('database.default')); dump(config('database.connections.mysql.database'));"
```

Expected release:

```text
mysql
stockedp
```

## 3. Database Tidak Dibuat Otomatis oleh Migration

`php artisan migrate` maupun `php artisan migrate:fresh` tidak bertanggung jawab membuat schema database MySQL baru. Database `stockedp` harus sudah dibuat oleh administrator MySQL/deployment infrastructure.

Contoh:

```sql
CREATE DATABASE stockedp
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Setelah database ada, migration dapat dijalankan.

## 4. Fresh Installation Release

Urutan canonical untuk database release baru:

```text
CREATE DATABASE stockedp
→ set DB_DATABASE=stockedp
→ php artisan migrate
→ php artisan db:seed --class=RoleAndPermissionSeeder
→ php artisan app:create-initial-admin
```

Pada production gunakan `--force` bila command memerlukannya:

```bash
php artisan migrate --force
php artisan db:seed --class=RoleAndPermissionSeeder --force
php artisan app:create-initial-admin
```

Jangan gunakan `migrate:fresh` pada database operasional/release.

## 5. Seeder Rules

### RoleAndPermissionSeeder

- Canonical release bootstrap seeder.
- Deterministik/idempotent.
- Aman digunakan untuk resync role/permission.

### ReleaseVerificationSeeder

- Hanya untuk local/testing/rehearsal.
- Membentuk dataset verifikasi sekitar 1.000 products, 5 locations, 5.000 balances, 10.000 movements.
- Bukan production seed.
- Menolak production environment.

### DatabaseSeeder

Historis Version 1: `DatabaseSeeder` memanggil `RoleAndPermissionSeeder` dan membuat user factory `admin@example.com`; factory memiliki default password test `password`.

Karena itu `php artisan migrate:fresh --seed` bukan prosedur bootstrap release.

Gunakan `app:create-initial-admin` untuk administrator awal.

## 6. Quantity & Precision

Semua quantity inventory wajib:

```text
DECIMAL(14,4)
```

PHP:

```text
decimal string + BCMath / DecimalQuantity
```

Dilarang menjadikan float sebagai sumber perhitungan inventory.

Nilai canonical:

```text
0.0000
1.2500
-0.0001
9999999999.9999
```

Negative zero harus dinormalisasi ke:

```text
0.0000
```

## 7. Inventory Balance & Movement Invariant

Mutasi stok harus atomic:

```text
lock location/freeze state
→ lock document
→ lock inventory balance
→ validate
→ write stock movement
→ update balance
→ commit
```

Invariant ledger:

```text
quantity_after = quantity_before + signed_delta
```

Setiap movement harus traceable melalui minimal:

```text
product_id
location_id
reference_type
reference_id
user_id
quantity_before
quantity_after
occurred_at
```

## 8. Global Locking / Freeze

`inventory_location_locks` dipakai untuk freeze lokasi pada stock opname.

Saat lokasi frozen:

- Receipt/Issue/Transfer/Adjustment normal yang menyentuh lokasi tersebut ditolak.
- Canonical response: `409 LOCATION_FROZEN`.
- Tidak boleh ada balance/movement parsial.

Global lock ordering harus deterministic untuk mengurangi deadlock. Untuk multi-location, location IDs diurutkan ascending sebelum lock.

## 9. Backup / Restore

Backup production database contoh:

```bash
mysqldump --host=127.0.0.1 --port=3306 --user=<USER> --password \
  --single-transaction --quick --routines --triggers --events \
  --default-character-set=utf8mb4 stockedp > stockedp_backup.sql
```

Password harus melalui prompt/interaktif atau secret manager; jangan taruh secret plaintext di command history/dokumentasi.

Restore drill harus dilakukan ke database terpisah terlebih dahulu.

## 10. Release Recoverability Evidence V1

Fase 10C-1 menggunakan:

```text
source  : stockedp_release_rehearsal
restore : stockedp_release_restore_test
```

Hasil accepted:

- backup exit code 0
- restore exit code 0
- checksum recorded
- 22 table/pivot parity 100%
- RBAC parity PASS
- freeze-lock parity PASS
- ledger integrity PASS

## 11. Rules untuk AI

Jika task menyentuh database:

1. Jangan mengubah precision tanpa explicit decision.
2. Jangan menggunakan `migrate:fresh` pada production/release.
3. Jangan memasukkan ReleaseVerificationSeeder ke DatabaseSeeder production.
4. Jangan membuat default admin/password hard-coded.
5. Jangan menghapus posted movements sebagai cara koreksi.
6. Gunakan transaction + locking untuk mutasi stok.
7. Tambahkan migration forward-only untuk perubahan schema release.
8. Jalankan test rollback, concurrency, dan reconciliation jika mutation logic berubah.
