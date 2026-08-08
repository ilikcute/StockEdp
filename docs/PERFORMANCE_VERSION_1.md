# Laporan Pengujian Performa API (Performance Version 1)

Dokumen ini mendokumentasikan target, profil dataset pengujian, serta matriks pengukuran performa waktu respons HTTP API pada Sistem Inventory Version 1.

---

## 1. Status Dokumen
Status: **DATASET VERIFIED — PENGUKURAN PERFORMANCE PENDING STAGE 10C**

> **Catatan Verifikasi (08-Agu-2026):**
> Dataset release (`ReleaseVerificationSeeder`) terverifikasi ter-seed lengkap dan valid pada database rehearsal bersih (`InventorySystemRehearsal`) — `ReleaseDatasetIntegrityTest` **PASS 56/56** (104.948 assertion). Pengukuran waktu respons HTTP di bawah belum dieksekusi dan menunggu Stage 10C benchmark.

---

## 2. Target Performa Rilis

> **TARGET UTAMA**: Waktu respons maksimal untuk seluruh operasi umum (JSON paginated list, pencarian, dan perincian data) adalah **2000 ms (2 detik / 2.000 milidetik)** pada lingkungan pengujian lokal representatif.

---

## 3. Profil Environment & Dataset Pengujian

- **Profil Benchmark**: Version 1 Local Release Verification Profile
- **Dataset Seeder**: `ReleaseVerificationSeeder`
- **Volume Dataset**:
  - Categories: 20
  - Units: 10
  - Products: 1.000
  - Suppliers: 50
  - Locations: 5
  - Users: 20
  - Inventory Balances: 5.000 rows
  - Stock Movements: 10.000 rows

---

## 4. Matriks Pengukuran Performa API

| Endpoint / Operasi API | Dataset Profile | Page Size | Number of Runs | Target (ms) | Median (ms) | Maximum (ms) | Status Target (2000 ms) |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | :---: |
| `GET /api/v1/products` | 1.000 Products | 15 | 5 | 2000 | TBD | TBD | **NOT EXECUTED** |
| `GET /api/v1/products?search=Verification` | 1.000 Products | 15 | 5 | 2000 | TBD | TBD | **NOT EXECUTED** |
| `GET /api/v1/products?search=REL-SKU-0500` | 1.000 Products | 15 | 5 | 2000 | TBD | TBD | **NOT EXECUTED** |
| `GET /api/v1/locations` | 5 Locations | 15 | 5 | 2000 | TBD | TBD | **NOT EXECUTED** |
| `GET /api/v1/stock-receipts` | Mixed Documents | 15 | 5 | 2000 | TBD | TBD | **NOT EXECUTED** |
| `GET /api/v1/stock-issues` | Mixed Documents | 15 | 5 | 2000 | TBD | TBD | **NOT EXECUTED** |
| `GET /api/v1/stock-transfers` | Mixed Documents | 15 | 5 | 2000 | TBD | TBD | **NOT EXECUTED** |
| `GET /api/v1/stock-adjustments` | Mixed Documents | 15 | 5 | 2000 | TBD | TBD | **NOT EXECUTED** |
| `GET /api/v1/stock-opnames` | Mixed Documents | 15 | 5 | 2000 | TBD | TBD | **NOT EXECUTED** |
| `GET /api/v1/inventory/balances` | 5.000 Balances | 15 | 5 | 2000 | TBD | TBD | **NOT EXECUTED** |
| `GET /api/v1/reports/low-stock` | 1.000 Products | 15 | 5 | 2000 | TBD | TBD | **NOT EXECUTED** |
| `GET /api/v1/reports/stock-card` | 10.000 Movements | 15 | 5 | 2000 | TBD | TBD | **NOT EXECUTED** |
| `GET /api/v1/reports/inventory-balances` | 5.000 Balances | 15 | 5 | 2000 | TBD | TBD | **NOT EXECUTED** |
