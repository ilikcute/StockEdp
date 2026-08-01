# Arsitektur Sistem Inventory

Dokumen ini menjelaskan struktur folder, aliran data, batas tanggung jawab,
dan aturan arsitektur aplikasi Sistem Inventory.

## Prinsip Utama

- Struktur aplikasi menggunakan pendekatan feature-first.
- Setiap fitur harus mandiri dan memiliki tanggung jawab yang jelas.
- Backend Laravel menangani business logic dan integritas data.
- Frontend Vue menangani tampilan, interaksi pengguna, dan state antarmuka.
- Pinia tidak boleh menjadi tempat utama business logic.
- Perubahan stok harus diproses oleh backend dalam database transaction.
- Kode bersama hanya ditempatkan di `Shared` jika digunakan minimal dua fitur.

## Struktur Proyek

```text
project-root/
├── backend/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── storage/
│   └── tests/
│
└── frontend/
    ├── src/
    ├── public/
    ├── tests/
    └── package.json
```

Jika Laravel dan Vue berada dalam satu repository Laravel, folder frontend
dapat menggunakan `resources/js/` sebagai pengganti `frontend/src/`.

---

# Backend Laravel

## Struktur Folder Backend

```text
backend/app/
├── Features/
│   ├── Auth/
│   ├── Product/
│   ├── Category/
│   ├── Unit/
│   ├── Supplier/
│   ├── Warehouse/
│   ├── Inventory/
│   ├── StockMovement/
│   ├── StockAdjustment/
│   ├── StockOpname/
│   └── Reporting/
│
├── Shared/
│   ├── Contracts/
│   ├── DTOs/
│   ├── Enums/
│   ├── Exceptions/
│   ├── Helpers/
│   ├── Services/
│   └── Traits/
│
└── Providers/
```

Setiap fitur backend memiliki struktur mandiri:

```text
app/Features/[Feature]/
├── Actions/
├── DTOs/
├── Enums/
├── Events/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Jobs/
├── Listeners/
├── Models/
├── Policies/
├── Repositories/
│   ├── Contracts/
│   └── Eloquent/
├── Routes/
├── Services/
└── Tests/
    ├── Feature/
    └── Unit/
```

Tidak semua folder wajib dibuat. Buat folder hanya ketika fitur
membutuhkannya.

## Tanggung Jawab Backend

### Controller

Controller bertanggung jawab untuk:

- Menerima HTTP request.
- Menjalankan authorization.
- Memanggil Form Request untuk validasi.
- Memanggil Action atau Service.
- Mengembalikan API Resource atau response JSON.

Controller tidak boleh:

- Berisi query database.
- Menghitung atau mengubah stok secara langsung.
- Berisi business logic.
- Membuka database transaction kompleks.
- Mengakses model fitur lain secara langsung untuk proses bisnis.

Contoh aliran:

```text
Request
  → Form Request
  → Controller
  → Action
  → Service
  → Repository
  → Model/MySQL
```

### Action

Action menangani satu use case aplikasi.

Contoh:

```text
CreateProductAction
ReceiveStockAction
IssueStockAction
TransferStockAction
AdjustStockAction
CompleteStockOpnameAction
```

Satu Action sebaiknya memiliki satu method utama, misalnya `execute()`.

Action dapat:

- Membuka database transaction.
- Mengkoordinasikan beberapa Service.
- Memanggil lebih dari satu Repository.
- Mengirim Event setelah transaksi berhasil.
- Menghasilkan domain result atau Model.

### Service

Service berisi business logic yang dapat digunakan oleh lebih dari satu Action.

Contoh:

```text
StockAvailabilityService
StockMovementService
InventoryValuationService
StockOpnameReconciliationService
```

Service tidak boleh bergantung pada Controller, Form Request, atau API Resource.

### Repository

Repository menjadi abstraksi akses data.

Repository bertanggung jawab untuk:

- Menjalankan query database.
- Menyimpan dan mengambil Model.
- Menyediakan query kompleks yang digunakan fitur.
- Mengunci data stok ketika diperlukan.

Repository tidak boleh:

- Menentukan response HTTP.
- Mengakses request secara langsung.
- Menangani tampilan frontend.
- Menjadi tempat seluruh business logic.

Interface repository disimpan di:

```text
app/Features/[Feature]/Repositories/Contracts/
```

Implementasi Eloquent disimpan di:

```text
app/Features/[Feature]/Repositories/Eloquent/
```

### Model

Model Eloquent menangani:

- Relasi database.
- Attribute casting.
- Query scope sederhana.
- Mutator dan accessor sederhana.
- Perilaku yang benar-benar melekat pada entity.

Model tidak boleh menjadi tempat alur transaksi bisnis yang kompleks.

### DTO

DTO membawa data antar-layer dengan struktur yang jelas.

DTO tidak boleh:

- Mengakses database.
- Mengakses request secara langsung.
- Menghasilkan HTTP response.
- Mengandung query Eloquent.

### Form Request

Form Request menangani:

- Validasi input.
- Normalisasi input sederhana.
- Authorization yang terkait request.

Aturan bisnis seperti ketersediaan stok tetap diperiksa di Action atau Service,
bukan hanya di Form Request.

### API Resource

API Resource menjadi satu-satunya tempat transformasi Model menjadi response API.

Controller tidak boleh menyusun response entity secara manual apabila sudah
tersedia API Resource.

## Aliran Data Backend

```text
HTTP Request
    ↓
Form Request
    ↓
Controller
    ↓
Action
    ↓
Service
    ↓
Repository
    ↓
Eloquent Model
    ↓
MySQL
```

Aliran response:

```text
MySQL
    ↓
Eloquent Model
    ↓
Repository
    ↓
Action
    ↓
API Resource
    ↓
JSON Response
```

## Batas Dependency Backend

| Layer | Boleh menggunakan | Tidak boleh menggunakan |
|---|---|---|
| Controller | Form Request, Action, Resource | Model langsung, query database |
| Action | DTO, Service, Repository, Event | Request global, Resource |
| Service | DTO, Repository, Model, Shared | Controller, Request, Resource |
| Repository | Model, Query Builder, Shared | Controller, Request, Resource |
| Model | Eloquent, Enum, Value Object | Controller, Action, Resource |
| Resource | Model, Resource lain | Repository, Service, Action |

Pelanggaran batas dependency harus dijelaskan dalam `DECISIONS.md`.

---

# Frontend Vue.js

## Struktur Folder Frontend

```text
frontend/src/
├── features/
│   ├── auth/
│   ├── products/
│   ├── categories/
│   ├── units/
│   ├── suppliers/
│   ├── warehouses/
│   ├── inventory/
│   ├── stock_movements/
│   ├── stock_adjustments/
│   ├── stock_opname/
│   └── reports/
│
├── shared/
│   ├── api/
│   ├── components/
│   ├── composables/
│   ├── constants/
│   ├── layouts/
│   ├── utils/
│   └── validators/
│
├── router/
├── assets/
├── App.vue
└── main.js
```

Setiap fitur frontend memiliki struktur:

```text
src/features/[feature]/
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

Tidak semua folder wajib dibuat. Folder dibuat sesuai kebutuhan fitur.

## Aliran Data Frontend

```text
Page/Component
    ↓
Composable
    ↓
Pinia Store
    ↓
Feature API
    ↓
Laravel REST API
```

Aliran response:

```text
Laravel REST API
    ↓
Feature API
    ↓
Pinia Store
    ↓
Composable
    ↓
Page/Component
```

Untuk operasi sederhana yang tidak membutuhkan shared state, composable boleh
memanggil Feature API secara langsung.

Komponen tidak boleh memanggil API secara langsung.

## State Management Pinia

Pinia digunakan untuk state yang:

- Digunakan oleh lebih dari satu komponen.
- Digunakan oleh beberapa halaman dalam satu fitur.
- Perlu dipertahankan ketika navigasi halaman.
- Memiliki status loading, error, filter, pagination, atau cache data.

Contoh lokasi store:

```text
src/features/products/stores/use_product_store.js
src/features/inventory/stores/use_inventory_store.js
```

Aturan Pinia:

- Satu store mewakili satu domain state yang jelas.
- Nama store diawali dengan `use`.
- Store tidak boleh berisi logic tampilan.
- Store tidak boleh memanipulasi DOM.
- Store tidak boleh berisi aturan bisnis utama persediaan.
- Error dari API harus dinormalisasi sebelum ditampilkan ke komponen.
- Jangan membuat satu store global untuk seluruh aplikasi.
- State form lokal tetap disimpan di component atau composable.

Contoh penamaan:

```js
useProductStore
useInventoryStore
useStockMovementStore
useStockOpnameStore
```

## Component dan Page

Page bertanggung jawab untuk:

- Menyusun layout sebuah halaman.
- Menghubungkan composable atau store.
- Mengatur interaksi antarkomponen.

Component bertanggung jawab untuk:

- Menampilkan data dari props.
- Mengirim event kepada parent.
- Menangani interaksi UI sederhana.

Component tidak boleh:

- Melakukan request API secara langsung.
- Mengandung aturan mutasi stok.
- Mengakses store fitur yang tidak berkaitan.
- Menjalankan query atau perhitungan bisnis kompleks.

Komponen lebih dari 150 baris harus diperiksa untuk dipisah jika memiliki
lebih dari satu tanggung jawab.

## Composable

Composable digunakan untuk logic frontend yang dapat digunakan kembali.

Contoh:

```text
use_product_form.js
use_stock_filter.js
use_pagination.js
use_api_error.js
```

Composable fitur diletakkan di dalam fitur terkait.

Composable ditempatkan di `shared/composables/` hanya jika digunakan oleh
minimal dua fitur.

## API Client

API fitur diletakkan di:

```text
src/features/[feature]/api/
```

Contoh:

```text
src/features/products/api/product_api.js
src/features/inventory/api/inventory_api.js
```

API client bertanggung jawab untuk:

- Memanggil endpoint Laravel.
- Mengirim parameter request.
- Mengembalikan data response.
- Menormalisasi error transport jika diperlukan.

API client tidak boleh:

- Mengubah tampilan.
- Mengakses DOM.
- Menampilkan notifikasi secara langsung.
- Menyimpan state antarmuka.

## Batas Dependency Frontend

| Layer | Boleh menggunakan | Tidak boleh menggunakan |
|---|---|---|
| Pages | components, composables, stores, shared | API fitur lain langsung |
| Components | props, events, shared components | API langsung, business logic |
| Composables | stores, feature API, shared | DOM kompleks, backend model |
| Pinia stores | feature API, shared utilities | components, pages, router view |
| Feature API | shared API client, types | components, pages, stores |
| Shared | library umum | fitur tertentu |

Sebuah fitur tidak boleh mengimpor internal file fitur lain secara langsung.

Jika fitur membutuhkan data fitur lain, gunakan salah satu dari:

- Endpoint backend yang sesuai dengan use case.
- Public export fitur.
- Shared contract.
- Event antarmodul.

---

# Aturan Modul Inventory

## Perubahan Stok

Semua perubahan stok harus menghasilkan stock movement.

Jenis perubahan stok minimal:

```text
RECEIPT
ISSUE
TRANSFER_IN
TRANSFER_OUT
ADJUSTMENT_IN
ADJUSTMENT_OUT
OPNAME_IN
OPNAME_OUT
```

Stok tidak boleh diperbarui langsung dari Controller, Repository umum,
command manual, atau frontend.

Aliran wajib perubahan stok:

```text
Controller
    ↓
Stock Action
    ↓
Database Transaction
    ├── Lock saldo stok
    ├── Validasi ketersediaan
    ├── Simpan stock movement
    ├── Perbarui saldo stok
    └── Simpan audit trail
```

Jika salah satu proses gagal, seluruh transaction harus dibatalkan.

## Aturan Integritas Stok

- Gunakan row locking untuk transaksi stok yang dapat berjalan bersamaan.
- Cegah stok negatif kecuali diizinkan oleh konfigurasi bisnis.
- Quantity transaksi harus lebih besar dari nol.
- Transfer stok harus memiliki lokasi asal dan tujuan berbeda.
- Transfer menghasilkan movement keluar dan masuk.
- Stock opname tidak langsung menimpa saldo tanpa rekonsiliasi.
- Setiap movement harus memiliki nomor referensi dan sumber transaksi.
- Movement yang sudah diposting tidak boleh dihapus secara permanen.
- Koreksi dilakukan menggunakan reversal atau adjustment baru.
- Gunakan decimal untuk quantity dan nilai persediaan, bukan float.

---

# Lokasi File

## Backend

| Kebutuhan | Lokasi |
|---|---|
| Endpoint fitur | `app/Features/[Feature]/Routes/` |
| Controller | `app/Features/[Feature]/Http/Controllers/` |
| Validasi request | `app/Features/[Feature]/Http/Requests/` |
| Response API | `app/Features/[Feature]/Http/Resources/` |
| Use case | `app/Features/[Feature]/Actions/` |
| Business logic | `app/Features/[Feature]/Services/` |
| Interface repository | `app/Features/[Feature]/Repositories/Contracts/` |
| Implementasi repository | `app/Features/[Feature]/Repositories/Eloquent/` |
| Model | `app/Features/[Feature]/Models/` |
| Migration | `database/migrations/` |
| Unit test | `tests/Unit/Features/[Feature]/` |
| Feature test | `tests/Feature/[Feature]/` |

## Frontend

| Kebutuhan | Lokasi |
|---|---|
| Halaman baru | `src/features/[feature]/pages/` |
| Komponen fitur | `src/features/[feature]/components/` |
| Pinia store | `src/features/[feature]/stores/` |
| API fitur | `src/features/[feature]/api/` |
| Composable fitur | `src/features/[feature]/composables/` |
| Route fitur | `src/features/[feature]/router/` |
| Komponen bersama | `src/shared/components/` |
| Layout aplikasi | `src/shared/layouts/` |
| Utility bersama | `src/shared/utils/` |
| Konstanta fitur | Di dalam folder fitur terkait |
| Konstanta global | `src/shared/constants/` |

---

# Routing

## Backend

Setiap fitur dapat memiliki file route sendiri:

```text
app/Features/Product/Routes/api.php
app/Features/Inventory/Routes/api.php
app/Features/StockOpname/Routes/api.php
```

Route fitur didaftarkan melalui Service Provider.

Semua endpoint menggunakan prefix:

```text
/api/v1/
```

Contoh:

```text
GET    /api/v1/products
POST   /api/v1/products
GET    /api/v1/inventory/stocks
POST   /api/v1/inventory/receipts
POST   /api/v1/inventory/issues
POST   /api/v1/inventory/transfers
POST   /api/v1/stock-opnames
```

## Frontend

Route setiap fitur didefinisikan di folder fitur dan digabungkan oleh router utama.

```text
src/features/products/router/product_routes.js
src/features/inventory/router/inventory_routes.js
```

Router utama tidak boleh berisi seluruh definisi halaman aplikasi.

---

# Pengujian

## Backend

- Unit test untuk Service dan aturan bisnis.
- Feature test untuk endpoint API.
- Integration test untuk perubahan stok.
- Test transaksi bersamaan untuk mencegah race condition.
- Test rollback ketika salah satu proses perubahan stok gagal.
- Test authorization untuk setiap operasi penting.

## Frontend

- Unit test untuk utility, composable, dan Pinia store.
- Component test untuk komponen penting.
- Test loading, empty state, validation error, dan API error.
- Test bahwa tombol transaksi tidak dapat dikirim berulang kali.

---

# Keputusan Arsitektur

Keputusan yang menyimpang dari dokumen ini harus dicatat dalam:

```text
DECISIONS.md
```

Setiap keputusan minimal berisi:

- Konteks masalah.
- Pilihan yang dipertimbangkan.
- Keputusan yang dipilih.
- Alasan keputusan.
- Konsekuensi dan trade-off.
- Tanggal keputusan.