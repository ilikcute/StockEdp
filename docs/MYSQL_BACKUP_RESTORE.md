# Prosedur Backup dan Restore MySQL

Dokumen ini mendokumentasikan prosedur standar untuk pembuatan backup database MySQL dan pemulihan data (restore drill) secara aman pada Sistem Inventory Version 1.

---

## 1. Prosedur Backup MySQL (`mysqldump`)

Pembuatan backup dilakukan menggunakan utility `mysqldump` dengan prompt password interaktif (tanpa mencantumkan password plaintext pada perintah shell).

```powershell
# Buat folder backup di luar repository proyek
New-Item -ItemType Directory -Force -Path "D:\Backups\StockEdp"

# Eksekusi backup dengan timestamp
mysqldump `
  --host="127.0.0.1" `
  --port="3306" `
  --user="root" `
  --password `
  --single-transaction `
  --quick `
  --routines `
  --triggers `
  --events `
  --default-character-set=utf8mb4 `
  "stockedp" `
  > "D:\Backups\StockEdp\stockedp_backup_YYYYMMDD_HHMMSS.sql"
```

### Verifikasi Hashes (Checksum) Backup
Setelah backup selesai, hasilkan checksum SHA-256 untuk memverifikasi integritas file:

```powershell
Get-FileHash `
  "D:\Backups\StockEdp\stockedp_backup_YYYYMMDD_HHMMSS.sql" `
  -Algorithm SHA256
```

---

## 2. Prosedur Restore MySQL (Restore Drill)

> **ATURAN UTAMA**: Restore wajib dilakukan ke database uji/terpisah terlebih dahulu (`stockedp_restore_test`) untuk mencegah kerusakan pada database operasional aktif.

### Langkah 1: Buat Database Restore Test Terpisah

```powershell
mysql `
  --host="127.0.0.1" `
  --port="3306" `
  --user="root" `
  --password `
  -e "CREATE DATABASE IF NOT EXISTS stockedp_restore_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Langkah 2: Import File SQL Backup ke Database Restore Test

```powershell
mysql `
  --host="127.0.0.1" `
  --port="3306" `
  --user="root" `
  --password `
  "stockedp_restore_test" `
  < "D:\Backups\StockEdp\stockedp_backup_YYYYMMDD_HHMMSS.sql"
```

### Langkah 3: Verifikasi Integritas Data Hasil Restore

Jalankan query verifikasi pada database restore test:
1. Hitung jumlah tabel dan record master (`users`, `products`, `locations`).
2. Hitung jumlah transaksi (`stock_receipts`, `stock_issues`, `stock_transfers`, `stock_adjustments`, `stock_opnames`).
3. Pastikan `inventory_balances` sesuai dengan akumulasi movement delta.
4. Hapus database uji setelah verifikasi selesai:
   ```powershell
   mysql -u root -p -e "DROP DATABASE stockedp_restore_test;"
   ```
