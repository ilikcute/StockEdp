# StockEdp — Frontend Context

## 1. Stack & Lokasi

- Vue.js 3
- Pinia
- Axios melalui shared API client
- Vite
- Frontend berada di `resources/js/`

Shared client canonical:

```text
resources/js/shared/api/api_client.js
```

## 2. Flow Canonical

```text
Page / Component
→ Composable atau Pinia Store
→ Feature API
→ shared apiClient
→ Laravel REST API
```

Response:

```text
Laravel REST
→ Feature API
→ Store/Composable
→ Page/Component
```

Komponen `.vue` tidak boleh melakukan raw Axios/fetch untuk business API.

## 3. Struktur Feature-First

Gunakan pola:

```text
resources/js/features/[feature]/
├── api/
├── components/
├── composables/
├── pages/
├── router/
├── stores/
├── types/
├── utils/
└── validators/
```

Shared reusable lintas fitur:

```text
resources/js/shared/
├── api/
├── components/
├── composables/
├── constants/
├── layouts/
├── utils/
└── validators/
```

Jangan memasukkan kode feature-specific ke shared hanya untuk menghindari import.

## 4. Page, Component, Composable, Store

### Page

- menyusun layout halaman;
- menghubungkan store/composable;
- orchestration UI.

### Component

- render props;
- emit event;
- interaksi UI sederhana.

Tidak boleh:

- direct API;
- inventory business arithmetic;
- business authorization sebagai satu-satunya guard.

Komponen >150 baris harus diperiksa apakah punya lebih dari satu responsibility dan perlu dipecah.

### Composable

Untuk logic UI/reusable workflow.
Feature composable tetap di feature; pindah shared hanya jika benar-benar lintas fitur.

### Pinia

Gunakan untuk shared feature state, loading/error/filter/pagination/cache.

Jangan:

- menyimpan DOM logic;
- menjadikan store tempat business rules inventory;
- membuat satu global mega-store.

## 5. Feature API

Feature API adalah satu-satunya layer feature yang berkomunikasi dengan shared Axios client.

Tanggung jawab:

- endpoint;
- method;
- params/payload;
- response extraction;
- transport error normalization jika diperlukan.

Tidak bertanggung jawab atas UI/notifikasi/state component.

## 6. Shared apiClient Contract

`api_client.js`:

- base URL dari `VITE_API_BASE_URL`, fallback `/api/v1`;
- timeout dari `VITE_API_TIMEOUT_MS`, fallback 10000 ms;
- credentials/XSRF aktif;
- Accept JSON;
- 401 selain `/auth/me` mengarahkan ke login;
- menyediakan `normalizeApiError()`.

Jangan bypass client ini tanpa keputusan arsitektur baru.

## 7. Authorization & Abilities

Frontend boleh menggunakan abilities dari API untuk enable/disable/hide action, tetapi:

```text
backend Policy/Action = authoritative security
frontend abilities     = UX guard
```

Maker-checker harus tercermin benar pada UI:

- Adjustment creator tidak mendapat Post action.
- Opname creator/counter participant tidak mendapat Post action.

Jika API Resource mengirim abilities, gunakan abilities tersebut dan jangan duplikasi policy kompleks di JS.

## 8. Quantity Precision Frontend

Quantity inventory datang sebagai decimal string.

Dilarang sebagai arithmetic source-of-truth:

```js
Number(quantity)
parseFloat(quantity)
quantity.toFixed(...)
```

Jangan hitung ulang balance/variance/direction di frontend jika backend sudah memberi nilai authoritative.

Frontend boleh memformat display secara aman, tetapi jangan mengubah nilai domain lalu mengirim hasil float kembali sebagai quantity authoritative.

## 9. Reporting UI

8 laporan:

- inventory balances;
- low stock;
- stock card;
- receipts;
- issues;
- transfers;
- adjustments;
- opnames.

UI harus mendukung filter sesuai endpoint/feature dan location scope user.

Stock card jangan menentukan direction sendiri; gunakan field backend.

## 10. CSV Export UI

Download 8 CSV menggunakan endpoint backend streamed export.

Rules UX:

- preserve filter aktif;
- handle 401/403/422/429;
- 429 tidak boleh logout atau mereset form;
- jangan parsing/rebuild CSV di browser;
- jangan menyimpan credential pada URL/log.

## 11. Stock Opname UI

Saat `IN_PROGRESS` dan user counter:

- blind count harus dipertahankan;
- jangan menampilkan snapshot/variance jika API memang menyembunyikannya;
- handle optimistic concurrency/count version conflict;
- handle `LOCATION_FROZEN`/state conflict secara eksplisit.

## 12. Responsive Acceptance V1

Canonical UAT mencakup:

```text
1280 × 800
1024 × 768
```

Perubahan layout utama harus memastikan core pages tetap usable di dua viewport tersebut.

## 13. Error State

Setiap feature page yang melakukan request harus mempertimbangkan:

```text
loading
empty
success
validation error
403
409
429
network failure
```

Jangan menganggap semua error adalah session expiration.

## 14. Quality Gate

Untuk perubahan frontend:

```bash
node node_modules/eslint/bin/eslint.js resources/js --max-warnings 0
npm run build
```

Jika perubahan memengaruhi API contract, jalankan backend tests relevan juga.

## 15. Audit Final V1

Independent final audit menemukan:

```text
Raw business Axios/fetch dari .vue : 0
Architecture layering              : PASS
ESLint                              : 0 errors / 0 warnings
Vite production build              : PASS
APP_DEBUG=false runtime smoke       : PASS
```

Pertahankan kondisi ini pada perubahan berikutnya.

## 16. Master Data Bulk Import Modal & Flow (Fase 11A)

- Lokasi feature: `resources/js/features/master_data_import/`
  - `api/master_data_import_api.js`: API helper untuk download template (blob), validate CSV (multipart), dan commit import (multipart + expectedSha256).
  - `composables/use_master_data_import.js`: Reusable composable untuk state & flow import masal.
  - `components/`: Modular UI (`MasterDataImportModal.vue` orchestrator, `MasterDataImportInstructions.vue`, `MasterDataImportUploader.vue`, `MasterDataImportSummary.vue`, `MasterDataImportPreviewTable.vue`, `MasterDataImportErrorTable.vue`).
- Integrasi tombol: Terintegrasi pada halaman master `CategoryPage.vue`, `UnitPage.vue`, `LocationPage.vue`, dan `ProductPage.vue` dengan proteksi permission granular (`{type}.import`).

## 17. Operational Dashboard (Fase 12A)

- Lokasi feature: `resources/js/features/dashboard/`
  - `api/dashboard_api.js`: API helper untuk `GET /api/v1/dashboard`.
  - `composables/use_dashboard.js`: Composable state `dashboardData`, `filters`, `loading`, `error`.
  - `pages/DashboardPage.vue`: Orchestrator (0 direct HTTP/apiClient calls).
  - `components/`: `DashboardFilterBar.vue` (assignment-scoped options), `InventoryHealthCards.vue`, `OperationalQueueCards.vue`, `PeriodActivityCards.vue`, `DashboardAlertList.vue` (permission-aware buttons), `RecentInventoryActivity.vue`, `TopIssuedProducts.vue`, `TopReceivedProducts.vue`.
- Quantity display: 0 `parseFloat`, 0 `Number()`, 0 `toFixed()`. Direct decimal string display (`item.quantity ?? '0.0000'`).
- Quick navigation: Semantic `<button>` / `<router-link>` dengan focus state & keyboard support, terproteksi `authStore.hasPermission(permission)`.

## 18. Barcode Scanner & Mobile Warehouse UX (Fase 12B)

- Lokasi scanner: `resources/js/features/inventory/scanner/`
  - `components/BarcodeScannerPanel.vue`: Form input scanner responsif (touch target >= 44px, autofocus berulang, status badge, pencegahan form submit tidak sengaja).
  - `composables/use_inventory_barcode_scanner.js`: Sequential scan queue untuk menangani rapid scans tanpa drop event.
  - `utils/decimal_string.js`: Exact 4-decimal arithmetic (`normalizeDecimal4String`, `addDecimal4Strings`, `compareDecimal4Strings`) tanpa float/Number conversion.
- Integrasi Halaman Transaksi Persediaan:
  - `StockReceiptFormPage.vue`: Integrasi scanner, scan berulang menambah kuantitas `+1.0000` per `(product_id, location_id)`.
  - `StockIssueFormPage.vue`: Integrasi scanner, warning stok tersedia tanpa float arithmetic.
  - `StockTransferFormPage.vue`: Integrasi scanner, duplicate key `product_id`.
  - `StockOpnameCountPage.vue`: Integrasi scanner sebagai locator produk (tidak auto-increment `≠ +1.0000`), blind count mode dipertahankan, unexpected product flow terintegrasi.
- Layering & Responsive: 0 direct business `apiClient` pada seluruh file `.vue` yang disentuh. Responsif pada viewport `360x800`, `390x844`, `768x1024`, `1024x768`, `1280x800`.

## 19. Reorder & Replenishment Center (Fase 12C)

- Lokasi feature: `resources/js/features/replenishment/`
  - `api/replenishment_api.js`: API helper untuk `GET /api/v1/replenishment-recommendations` dan `filter-options`.
  - `composables/use_replenishment.js`: Composable state, reactive filters, pagination, summary metrics.
  - `components/`:
    - `ReplenishmentFilterBar.vue`: Location selector (assigned locations only), search, category/unit dropdowns, recommendation type & priority dropdowns, refresh button, timestamp.
    - `ReplenishmentSummaryCards.vue`: Product count summary cards (Low Stock, Inbound Covered, Internal Transfer, Mixed, External Reorder, Critical).
    - `ReplenishmentStatusBadge.vue`: Visual status badges for recommendation types and priorities.
    - `SourceAllocationList.vue`: Breakdown per sister warehouse with current stock, minimum stock, surplus, suggested transfer, and "Siapkan Transfer" action.
    - `ReplenishmentRecommendationTable.vue`: Data table displaying product, target stock, minimum, gross shortage, inbound, net need, recommendation type, source allocations, and actions.
  - `pages/ReplenishmentPage.vue`: Orchestrator (0 direct `apiClient` in `.vue`).
- Transfer Prefill:
  - Form `StockTransferFormPage.vue` menangkap query parameter (`origin_location_id`, `destination_location_id`, `product_id`, `quantity`, `source=replenishment`) saat mount dalam mode create.
  - Tidak melakukan auto-save atau mutasi otomatis.
- Quantity display: 0 `parseFloat`, 0 `Number()`, 0 `toFixed()`. Direct decimal string display.
