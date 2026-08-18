# StockEdp — Backend & Domain Context

## 1. Arsitektur Backend

Flow canonical:

```text
HTTP Request
→ Form Request
→ Controller
→ Action
→ Service (jika reusable business logic)
→ Repository
→ Eloquent Model / MySQL
→ API Resource
→ JSON
```

Rules:

- Controller tipis.
- Controller tidak melakukan query atau arithmetic stok langsung.
- Action menangani satu use case.
- Service untuk business logic reusable.
- Repository untuk data access/query/locking.
- API Resource untuk transform response.
- Policy/backend authorization adalah sumber kebenaran security.

Feature-first di `app/Features/[Feature]/...`.
Shared code hanya bila benar-benar digunakan lintas fitur.

## 2. Mutasi Stok

Semua mutasi stok harus:

1. di-authorize backend;
2. tervalidasi;
3. berjalan dalam DB transaction;
4. mengikuti lock ordering;
5. membuat stock movement;
6. memperbarui inventory balance atomic;
7. rollback penuh bila gagal.

Tidak boleh mengubah saldo langsung dari Controller/frontend/command ad-hoc.

## 3. Receipt

State utama:

```text
DRAFT → POSTED
```

POST harus:

- lock dokumen;
- validate location aktif/tidak frozen;
- lock balance;
- menghasilkan `RECEIPT` movement;
- update balance;
- mencegah double-post.

## 4. Issue

State utama:

```text
DRAFT → POSTED
```

POST harus:

- cek insufficient stock;
- cegah negative balance;
- menghasilkan `ISSUE` movement;
- rollback total pada kegagalan;
- idempotent terhadap transition ganda.

## 5. Stock Transfer

Nomor canonical:

```text
TRF-YYYYMM-XXXX
```

State/use case utama:

```text
Create / Update
→ SEND
→ RECEIVE
```

V1 tidak memakai partial receipt dan reversal transfer.

Rules:

- origin != destination;
- SEND menghasilkan efek outbound sesuai domain implementation;
- RECEIVE menghasilkan inbound;
- `TRANSFER_OUT` dan `TRANSFER_IN` harus traceable ke transfer yang sama;
- exact quantity harus conserved;
- lock location secara deterministic;
- state transition ganda tidak boleh menduplikasi movement.

## 6. Stock Adjustment

Status:

```text
DRAFT → POSTED
DRAFT → CANCELED
```

AdjustmentReason memiliki direction compatibility.

Maker-checker:

- creator/maker tidak boleh post dokumennya sendiri;
- authorized checker berbeda yang melakukan post.

Movement:

```text
ADJUSTMENT_IN
ADJUSTMENT_OUT
```

Reference/movement uniqueness harus mencegah duplicate posting.

## 7. Stock Opname

Status canonical:

```text
DRAFT
→ IN_PROGRESS
→ COUNTED
→ POSTED
```

Tambahan:

```text
COUNTED → IN_PROGRESS   (REOPEN dengan alasan/log)
DRAFT/IN_PROGRESS/COUNTED → CANCELED sesuai contract
```

Start:

- snapshot saldo;
- freeze location;
- create item snapshot.

Count:

- blind count;
- optimistic concurrency via `count_version`;
- immutable count log;
- unexpected product diperbolehkan dengan snapshot 0.

Complete:

- semua item harus dihitung;
- variance = counted - snapshot.

Post:

- maker-checker;
- creator atau counter participant tidak boleh post;
- cek snapshot drift;
- variance positif → `OPNAME_IN`;
- variance negatif → `OPNAME_OUT`;
- unfreeze setelah sukses.

Blind-count API saat `IN_PROGRESS` tidak boleh mengekspos snapshot/variance kepada counter.

## 8. Freeze Infrastructure

Tabel:

```text
inventory_location_locks
```

Field penting:

```text
location_id (PK/FK)
is_frozen
frozen_by_opname_id
frozen_at
```

Invariant state frozen/unfrozen dijaga DB/domain.

Lokasi baru wajib memiliki lock row; service/repository membuatnya concurrency-safe.

## 9. Lock Ordering

Gunakan satu order global/deterministic. Prinsip release final:

```text
location/freeze locks
→ document header
→ inventory balances
→ movement/update
```

Untuk banyak location/product pair, selalu sort deterministically sebelum lock.

Jangan memperkenalkan urutan lock baru secara lokal tanpa audit deadlock/concurrency.

## 10. DecimalQuantity

Helper shared reporting/inventory menggunakan decimal strings + BCMath.

Rules penting:

- scale = 4;
- `null` boleh dinormalisasi sesuai contract ke `0.0000`;
- negative zero → `0.0000`;
- invalid string ditolak;
- runtime float tidak diterima untuk inventory arithmetic.

## 11. Reporting

Canonical endpoints:

```text
GET /api/v1/reports/inventory-balances
GET /api/v1/reports/low-stock
GET /api/v1/reports/stock-card
GET /api/v1/reports/stock-receipts
GET /api/v1/reports/stock-issues
GET /api/v1/reports/stock-transfers
GET /api/v1/reports/stock-adjustments
GET /api/v1/reports/stock-opnames
```

Location scoping harus dikirim eksplisit ke repository; repository tidak boleh diam-diam mengambil auth context.

Stock-card authoritative ledger ordering menggunakan movement ID order (atau `occurred_at, id` sesuai query presentation) sehingga `quantity_before/after` tetap matematis walau business date backdated.

Direction report berasal dari delta balance, bukan asumsi movement label di frontend.

## 12. CSV Export

Semua 8 laporan memiliki `/export`.

V1 contract:

- UTF-8 BOM;
- synchronous `streamDownload`;
- cursor/LazyCollection memory-safe;
- deterministic order;
- no permanent file;
- no queue/background export;
- formula injection protection;
- decimal strings passthrough;
- allowed-location scoping;
- jika user tidak punya location scope: header-only/empty data tanpa leak.

## 13. Error / HTTP Contract

Status canonical yang penting:

```text
401 unauthenticated
403 unauthorized / location scope
409 domain conflict (contoh LOCATION_FROZEN, concurrency/state conflict)
422 validation
429 rate limit
```

429 harus mempertahankan `Retry-After`.
Production-like error tidak boleh mengekspos SQL/stack trace/credential/path sensitif.

## 14. Security

- Sanctum auth.
- Granular permission codes.
- Warehouse/location scoping wajib pada backend.
- IDOR harus ditolak bahkan jika user mengirim object ID langsung.
- Maker-checker enforcement harus ada di policy/action, bukan hanya UI.

## 15. Test Baseline Version 1

Final independent audit baseline:

```text
Normalized discovery : 371
Full suite           : 371/371 PASS
Assertions           : 106,228
Concurrency          : 16 tests / 34 assertions
Reporting            : 69 / 507
CSV Export           : 26 / 249
ReleaseIntegrity     : 56 / 104,948
Reconciliation       : 5 / 55,028
```

Jika source/test berubah, angka dapat naik. Jangan memaksa count tetap; yang wajib adalah tidak ada unexplained regression.

## 16. Master Data Bulk Import (Fase 11A)

- Entitas didukung: `products`, `categories`, `units`, `locations`.
- Format: CSV UTF-8 native (`SplFileObject` / `fgetcsv`) dengan BOM support dan limit 5.000 baris.
- Kontrak: **CREATE ONLY** (duplikat di DB atau file ditolak), **All-or-Nothing** transaksional.
- Verifikasi: SHA256 checksum mismatch ditolak dengan HTTP 409.
- Location import: memicu `LocationObserver` (`inventory_location_locks`), tanpa penugasan otomatis ke `user_locations`.
- Product import: resolusi kode kategori & satuan secara batch, preservasi barcode string (termasuk leading zero), minimum stock decimal 2 digit (DECIMAL(12,2)) dinormalisasi murni string/BCMath tanpa float.
- Tidak ada mutasi persediaan atau perubahan saldo stok yang terjadi.

## 17. Operational Dashboard (Fase 12A)

- Endpoint `GET /api/v1/dashboard` read-only (`delta = 0`).
- RBAC: `dashboard.view`. Location-scoped: `$user->getAllowedLocationIds()`. Unpermitted `location_id` returns `403`.
- Computed Alerts (zero notification tables): `OUT_OF_STOCK` (`CRITICAL`), `LOW_STOCK` (`WARNING`), `TRANSFER_AWAITING_RECEIPT`, `ADJUSTMENT_PENDING`, `OPNAME_IN_PROGRESS`, `OPNAME_AWAITING_POST`, `FROZEN_LOCATION` (`INFO`).
- Period Activity: Half-open interval `[start_of_day, start_of_next_day)` in `Asia/Jakarta`. Receipts & Issues use `created_at` posting timestamp basis; Transfers use `received_at` basis.
- Top Issued: `MovementType::ISSUE` ONLY. Top Received: `MovementType::RECEIPT` ONLY.
- Decimal Safety: 0 PHP float quantity arithmetic. `DecimalQuantity::normalize(...)` used for output formatting.

## 18. Barcode Lookup & Mobile Backend (Fase 12B)

- Endpoint: `GET /api/v1/products/barcode-lookup?barcode=...`
- Route Ordering: Didaftarkan sebelum `products/{product}` untuk mencegah tabrakan route-model binding.
- RBAC: `products.view`.
- Data Contract: Barcode string eksak (max 100). Leading zero dipertahankan utuh.
- Semantik Status: `404 BARCODE_NOT_FOUND` jika barcode tidak ada, `409 PRODUCT_INACTIVE` jika produk nonaktif, `200` dengan Product data jika aktif.
- Invariant Read-Only: 0 mutasi pada balances/movements (`delta = 0`).
- Performa: Menggunakan index `products.barcode` bawaan, respons endpoint < 1.000 ms.

## 19. Reorder & Replenishment Recommendation Center (Fase 12C)

- Endpoint: `GET /api/v1/replenishment-recommendations` dan `GET /api/v1/replenishment-recommendations/filter-options`.
- RBAC: `replenishment.view`. Diberikan ke `ADMIN`, `WAREHOUSE_OFFICER`, dan `INVENTORY_SUPERVISOR`.
- Invarian Read-Only: Strictly read-only (`delta = 0`), 0 persistent recommendation tables, 0 auto-generated transactions.
- Canonical Shared Query: Memusatkan logika low stock pada `App\Features\Reporting\Queries\LowStockQuery::forLocation()`, dipakai bersama oleh Reporting dan Replenishment (`minimum_stock > 0`, `on_hand < minimum_stock`, `gross_shortage = MAX(minimum_stock - on_hand, 0)`).
- Inbound Tracking: Hanya menghitung `TransferStatus::SENT` inbound ke gudang target; mengurangi kebutuhan bersih (`net_replenishment_need = MAX(gross_shortage - pending_inbound, 0)`). Jika tertutup penuh $\to$ `INBOUND_COVERED`.
- Source Surplus: Gudang sumber wajib mempertahankan `minimum_stock` miliknya (`surplus = MAX(source_on_hand - source_min_stock, 0)`).
- Frozen Location Safety: Gudang sumber beku (`is_frozen = true`) dieliminasi dari alokasi; target beku diset `actionable = false` dengan `blocked_reason = TARGET_LOCATION_FROZEN`.
- Filter & Pagination Semantics: Kandidat base Low Stock diperkaya dalam bulk (0 N+1), kemudian rekomendasi diturunkan, difilter berdasarkan `recommendation_type`, diurutkan, dan dipaginasi dengan metadata yang merujuk tepat pada filtered dataset.
- Summary Contract (Option A): Kartu metrik `summary` mengikuti filter basis aktif dan menampilkan total produk lintas seluruh tipe rekomendasi.
- Alokasi Deterministik: Greedy allocation (`available_surplus DESC`, `location_id ASC`).
- Security IDOR: Membatasi target dan kandidat sumber hanya pada `$user->getAllowedLocationIds()`.
- Decimal Arithmetic: 0 PHP float. Semua perhitungan kuantitas menggunakan BCMath scale 4.

## 20. Inventory Movement Intelligence (Slow & Fast Moving Items)

- Endpoint: `GET /api/v1/dashboard/inventory-movement-summary`, `GET /api/v1/reports/inventory-movement`, dan `GET /api/v1/reports/inventory-movement/export`.
- RBAC: `reports.inventory_movement.view` (juga dapat diakses dengan `reports.view` / `dashboard.view` untuk summary). Diberikan ke `ADMIN`, `WAREHOUSE_OFFICER`, dan `INVENTORY_SUPERVISOR`.
- Invarian Read-Only: Strictly read-only (`delta = 0`), zero database mutations, zero auto-actions.
- Canonical Movement Query (`InventoryMovementIntelligenceQuery`):
  - Slow Moving: `products.is_active = true` dengan `movement_count == 0` pada periode analisis (30, 60, 90, 120, 180, 365 hari). Menghitung `last_movement_at` dan `days_since_last_movement` berbasis tanggal server `Asia/Jakarta`.
  - Fast Moving: `products.is_active = true` dengan transaksi pengeluaran aktual (`MovementType::ISSUE`). Menghitung `total_outbound_quantity`, `outbound_movement_count`, `average_daily_outbound`, `movement_days`, dan `velocity_score`.
  - Internal transfer (`TRANSFER_IN`/`TRANSFER_OUT`), opname, penyesuaian, dan pembatalan tidak dihitung sebagai consumer demand.
- Security & IDOR: Query di-scope ketat ke `$user->getAllowedLocationIds()`.
- Decimal Safety: 0 PHP float. Kuantitas dinormalisasi dengan 4 digit desimal via BCMath scale 4.

