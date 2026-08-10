# StockEdp — Known Issues & Follow-Up Notes

Dokumen ini berisi hal yang bukan blocker Version 1 saat sertifikasi, tetapi penting agar AI berikutnya tidak mengulang kesalahan atau memperkenalkan risiko baru.

## 1. `.env.example` vs Release Database Name

Historis repository:

```text
.env.example       → DB_DATABASE=InventorySystem
release docs       → DB_DATABASE=stockedp
```

Canonical release database adalah:

```text
stockedp
```

Follow-up yang disarankan:

- selaraskan `.env.example` ke nama generik/official yang disepakati;
- pastikan docs tidak bertentangan;
- setelah perubahan config docs, jalankan lint/docs review yang relevan.

Jangan mengubah `.env` user yang berisi secret melalui commit.

## 2. `DatabaseSeeder` Tidak Cocok untuk Release Bootstrap

Historis `DatabaseSeeder`:

- memanggil `RoleAndPermissionSeeder`;
- membuat user `admin@example.com` via factory;
- factory memiliki default password test `password`.

Ini valid sebagai developer/test convenience hanya bila memang disengaja, tetapi tidak boleh menjadi prosedur release.

Canonical release bootstrap:

```text
php artisan migrate
php artisan db:seed --class=RoleAndPermissionSeeder
php artisan app:create-initial-admin
```

Potential improvement:

- review apakah default admin factory masih perlu di `DatabaseSeeder`;
- bila dihapus/diubah, jaga developer/test workflow dan tambahkan test/documentation sesuai dampak.

## 3. `migrate:fresh` Tidak Membuat MySQL Database

Laravel migration mengelola tabel dalam database yang sudah dipilih.

Jika `stockedp` belum ada, buat schema MySQL terlebih dahulu.

Jangan menambahkan logic CREATE DATABASE ke migration aplikasi tanpa keputusan eksplisit; database provisioning biasanya tanggung jawab deployment/infrastructure.

## 4. Version 1 Stable != Production Deployed

Status saat ini:

```text
STABLE
RELEASE READY
NOT DEPLOYED
NO TAG
NO GITHUB RELEASE
```

Deployment perlu task/gate terpisah.

## 5. Inventory Valuation / Accounting Deferred

Version 1 inventory valuation/accounting adalah deferred/not applicable sesuai accepted scope.

Jangan diam-diam mengaktifkan weighted-average/FIFO accounting logic hanya karena env flag/nama config ada.

Jika V2 mulai mengimplementasikan valuation/accounting, buat keputusan dan fase terpisah.

## 6. Release Dataset Bukan Capacity SLA

Dataset sekitar:

```text
~1,000 products
~5,000 balances
~10,000 movements
```

adalah release verification profile, bukan jaminan kapasitas production universal.

Performance V1 267.55ms max adalah hasil environment rehearsal tertentu, bukan SLA untuk semua hardware/data volume.

## 7. Do Not Reuse Old Evidence Across Source Mutation

Jika application source/test/migration/dependency berubah setelah certified SHA:

```text
674dbf2e5b4d047fd8a67fee91a04f8caeb2b613
```

jangan mempertahankan label release hanya berdasarkan test lama.

Lakukan regression sesuai risiko.

## 8. Documentation Skill Maintenance

Jika proyek berubah besar, update folder ini bersamaan dengan source/docs utama.

Minimal update ketika:

- database canonical berubah;
- architecture flow berubah;
- movement/status baru ditambah;
- authorization policy berubah;
- release status berubah;
- V2 scope dimulai;
- deployment production benar-benar dilakukan.

## 9. Native XLSX Import Deferred (CSV Excel-Compatible Only)

Pada Fase 11A, import masal master data difokuskan pada format CSV UTF-8 native tanpa dependensi pihak ketiga (`phpoffice/phpspreadsheet` / `maatwebsite/excel`).
- File `.xlsx` langsung belum didukung (user harus menyimpan/export sebagai CSV UTF-8 terlebih dahulu).
- Workflow CSV didesain kompatibel dengan Microsoft Excel (mendukung UTF-8 BOM, separator koma, dan CRLF).

