# Laporan Pengujian Performa API & Operasional (Performance Version 1) — EXECUTED & VERIFIED

Dokumen ini mendokumentasikan target, profil dataset pengujian, matriks pengukuran performa waktu respons HTTP API, matriks durasi alur kerja operasional manusia, serta pengujian gladi resik rilis produksi (continuous write rehearsal) pada Sistem Inventory Version 1.

---

## 1. Status Dokumen
Status: **STAGE 10C-3 CLOSED — ALL BENCHMARKS, OPERATIONAL TIMINGS & CONTINUOUS REHEARSAL PASS**

> **Catatan Pengukuran Performa (dieksekusi 08-Agu-2026 / 10-Agu-2026):**
> Benchmark dieksekusi pada database rehearsal terisolasi (`stockedp_release_uat`) yang berisi **1.004 Produk**, **5.006 Balances**, dan **10.015 Stock Movements** dengan `APP_DEBUG=false`. Seluruh 17 API endpoint kanonikal memiliki waktu respons maksimum global **267.55 ms** (`GET /api/v1/reports/stock-receipts`), jauh di bawah ambang batas **2000 ms (2 detik)**.
> 10 alur kerja operasional manusia (human browser operational workflows) telah diukur dan dibuktikan selesai dalam durasi **1.815s – 12.637s**, memenuhi target **< 60 detik** per alur kerja (PRD Kriteria #13).

---

## 2. Target Performa Rilis

> **TARGET UTAMA**: Waktu respons maksimal untuk seluruh operasi umum (JSON paginated list, pencarian, dan perincian data) adalah **2000 ms (2 detik / 2.000 milidetik)** pada lingkungan pengujian lokal representatif.
> **TARGET OPERASIONAL**: Seluruh 10 alur kerja transaksi utama manusia dari login hingga penyelesaian dokumen dapat diselesaikan dalam waktu **< 60 detik**.

---

## 3. Profil Environment & Dataset Pengujian

- **Profil Benchmark**: Version 1 Local Release Verification Profile
- **Dataset Seeder**: `ReleaseVerificationSeeder`
- **Volume Dataset**:
  - Categories: 20
  - Units: 10
  - Products: 1.004
  - Suppliers: 50
  - Locations: 5
  - Users: 20
  - Inventory Balances: 5.006 rows
  - Stock Movements: 10.015 rows

---

## 4. Matriks Pengukuran Performa API Utama (17 Base Endpoints)

| Endpoint / Operasi API | Dataset Profile | Page Size | Number of Runs | Target (ms) | Min (ms) | Median (ms) | Maximum (ms) | Status Target (2000 ms) |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | :---: |
| `GET /api/v1/products` | 1.004 Products | 15 | 5 | 2000 | 12.25 | 12.61 | 12.80 | **PASS** |
| `GET /api/v1/products?search=PROD` | 1.004 Products | 15 | 5 | 2000 | 11.30 | 12.77 | 13.08 | **PASS** |
| `GET /api/v1/locations` | 5 Locations | 15 | 5 | 2000 | 7.97 | 8.22 | 8.98 | **PASS** |
| `GET /api/v1/stock-receipts` | Mixed Documents | 15 | 5 | 2000 | 15.72 | 16.82 | 17.75 | **PASS** |
| `GET /api/v1/stock-issues` | Mixed Documents | 15 | 5 | 2000 | 12.36 | 13.47 | 13.80 | **PASS** |
| `GET /api/v1/stock-transfers` | Mixed Documents | 15 | 5 | 2000 | 12.68 | 13.67 | 15.11 | **PASS** |
| `GET /api/v1/stock-adjustments` | Mixed Documents | 15 | 5 | 2000 | 30.65 | 31.91 | 34.04 | **PASS** |
| `GET /api/v1/stock-opnames` | Mixed Documents | 15 | 5 | 2000 | 46.33 | 50.43 | 51.76 | **PASS** |
| `GET /api/v1/inventory/balances` | 5.006 Balances | 15 | 5 | 2000 | 14.84 | 15.56 | 16.52 | **PASS** |
| `GET /api/v1/reports/low-stock` | 1.004 Products | 15 | 5 | 2000 | 17.21 | 17.45 | 18.80 | **PASS** |
| `GET /api/v1/reports/stock-card` | 10.015 Movements | 15 | 5 | 2000 | 11.32 | 12.56 | 13.33 | **PASS** |
| `GET /api/v1/reports/inventory-balances` | 5.006 Balances | 15 | 5 | 2000 | 14.96 | 16.69 | 17.54 | **PASS** |
| `GET /api/v1/reports/stock-receipts` | Mixed Documents | 15 | 5 | 2000 | 263.04 | 266.77 | **267.55** | **PASS** |
| `GET /api/v1/reports/stock-issues` | Mixed Documents | 15 | 5 | 2000 | 36.18 | 37.13 | 39.16 | **PASS** |
| `GET /api/v1/reports/stock-transfers` | Mixed Documents | 15 | 5 | 2000 | 34.19 | 36.52 | 43.07 | **PASS** |
| `GET /api/v1/reports/stock-adjustments` | Mixed Documents | 15 | 5 | 2000 | 49.90 | 53.85 | 54.19 | **PASS** |
| `GET /api/v1/reports/stock-opnames` | Mixed Documents | 15 | 5 | 2000 | 80.50 | 85.69 | 88.29 | **PASS** |

> **OVERALL MAXIMUM API RESPONSE TIME**: **267.55 ms** (Target <= 2000.00 ms) — **PASS**

---

## 5. Matriks Performa Suplemen (Filter, Search & Detail Endpoints)

| Supplemental Endpoint / Filter Operation | Runs | Target (ms) | Min (ms) | Median (ms) | Maximum (ms) | Status |
| --- | ---: | ---: | ---: | ---: | ---: | :---: |
| Product partial search (`GET /api/v1/products?search=PROD`) | 5 | 2000 | 16.54 | 17.52 | 18.88 | **PASS** |
| Exact SKU search (`GET /api/v1/products?search=PROD-000001`) | 5 | 2000 | 14.86 | 17.27 | 17.79 | **PASS** |
| Stock Card product filter (`GET /api/v1/reports/stock-card?product_id=1`) | 5 | 2000 | 19.01 | 23.66 | 25.89 | **PASS** |
| Date-filtered report (`GET /api/v1/reports/stock-receipts?start_date=...&end_date=...`) | 5 | 2000 | 18.25 | 19.43 | 20.09 | **PASS** |
| Location-filtered report (`GET /api/v1/reports/stock-issues?location_id=1`) | 5 | 2000 | 22.85 | 23.65 | 25.12 | **PASS** |
| Category-filtered operation (`GET /api/v1/products?category_id=1`) | 5 | 2000 | 13.95 | 17.42 | 26.54 | **PASS** |
| Status-filtered report (`GET /api/v1/reports/stock-adjustments?status=POSTED`) | 5 | 2000 | 17.76 | 20.62 | 46.53 | **PASS** |
| Product detail (`GET /api/v1/products/{id}`) | 5 | 2000 | 11.81 | 12.63 | 12.85 | **PASS** |
| Receipt detail (`GET /api/v1/stock-receipts/{id}`) | 5 | 2000 | 26.35 | 28.26 | 30.04 | **PASS** |
| Transfer detail (`GET /api/v1/stock-transfers/{id}`) | 5 | 2000 | 10.77 | 11.68 | 12.54 | **PASS** |
| Opname detail (`GET /api/v1/stock-opnames/{id}`) | 5 | 2000 | 56.02 | 62.65 | 88.27 | **PASS** |

---

## 6. Matriks Durasi Workflow Operasional Manusia (PRD Kriteria #13 < 60 detik)

| Operational Workflow ID | Executed Role | Operational Workflow Steps | Measured Duration | Target Limit | Status |
| --- | --- | --- | ---: | ---: | :---: |
| **Workflow 1** | Warehouse Officer | Create + Post Stock Receipt | 5.014 s | < 60.0 s | **PASS — VERIFIED** |
| **Workflow 2** | Warehouse Officer | Create + Post Stock Issue | 3.953 s | < 60.0 s | **PASS — VERIFIED** |
| **Workflow 3** | Warehouse Officer | Create + Send Stock Transfer | 4.145 s | < 60.0 s | **PASS — VERIFIED** |
| **Workflow 4** | Warehouse Officer | Receive Stock Transfer | 1.815 s | < 60.0 s | **PASS — VERIFIED** |
| **Workflow 5** | Warehouse Officer / Maker | Create Stock Adjustment Draft | 2.518 s | < 60.0 s | **PASS — VERIFIED** |
| **Workflow 6** | Inventory Supervisor / Checker | Review + Post Stock Adjustment | 2.219 s | < 60.0 s | **PASS — VERIFIED** |
| **Workflow 7** | Inventory Supervisor | Start Stock Opname Session | 2.753 s | < 60.0 s | **PASS — VERIFIED** |
| **Workflow 8** | Warehouse Counter | Input Count Quantities (All items) | 12.637 s | < 60.0 s | **PASS — VERIFIED** |
| **Workflow 9** | Counter / Supervisor | Complete Stock Opname Count | 2.845 s | < 60.0 s | **PASS — VERIFIED** |
| **Workflow 10** | Inventory Supervisor / Checker | Review + Post Stock Opname | 3.689 s | < 60.0 s | **PASS — VERIFIED** |

---

## 7. Hasil Gladi Resik Penulisan Berkelanjutan (Continuous Write Rehearsal)

 Gladi resik alur penulisan transaksi beruntun (Cold Restart -> Login -> Check Balances -> Receipt Post -> Issue Post -> Transfer Send/Receive -> Report Filter -> CSV Download -> Logout) dieksekusi dengan hasil:
- **Console Errors**: 0
- **Network 5xx Errors**: 0
- **Unexplained Log Errors**: 0
- **Post-Rehearsal Ledger Reconciliation**:
  - Negative Balances: 0 (Target = 0)
  - LOC-1 Balance (`215.0000`) == Net Movement Delta (`215.0000`) -> `bccomp` delta = 0 (**PASS**)
  - LOC-2 Balance (`40.0000`) == Net Movement Delta (`40.0000`) -> `bccomp` delta = 0 (**PASS**)

