# Audit Sistem & Kode — StockEdp

> Status: Pra-produksi (release audit) · Tanggal: 16 September 2026
> Lingkup: Backend, Frontend, Database — fokus performance, keandalan (reliability), akurasi, dan kemudahan operasional (ease of operation).
> Dokumen ini menggantikan `docs/AUDIT_SISTEM_DAN_KODE.md`.

---

## 1. Ringkasan Eksekutif

StockEdp adalah sistem manajemen persediaan/pergudangan berbasis **Laravel 13 (PHP 8.5) + MySQL 8** pada sisi server dan **Vue 3 + Pinia + Tailwind 4** pada sisi klien, dengan arsitektur *feature-based* (`app/Features/<Fitur>/{Routes,Http,Models,Services,Repositories,Actions}`). Seluruh API dimuat di prefix `api/v1` melalui `app/Shared/Providers/FeatureRouteServiceProvider.php`.

Basis kode secara umum **rapi dan terstruktur** — mesin pencatatan inventory (`StockMovementService`) sudah memakai transaksi + row locking + aritmetika desimal (bcmath), auth menggunakan session cookie Sanctum yang aman (tanpa token di localStorage), dan banyak pengamanan sudah benar.

**Namun sistem BELUM siap rilis** karena ada beberapa temuan blokir:

| Prioritas | Jumlah | Contoh Temuan |
|---|---|---|
| Critical | 1 | Suite test (582+ test) **tidak pernah hijau** dan **tidak deterministik** — dua run penuh memproduksi set kegagalan yang berbeda (39 vs 13 failure) |
| High | 2 | Query reporting/dashboard melakukan **full scan** karena `inventory_balances` kekurangan index `location_id`; **endpoint reporting/dashboard/replenishment tidak memeriksa permission** (hanya `auth:sanctum`) |
| Medium | 4 | Error 500 membocorkan pesan exception mentah; redirect login 401 menggugurkan query/hash; `StockReceiptPolicy` belum ada; operasional produksi (scheduler/queue/secure cookie) belum siap |

Detail, bukti path + line, dan rekomendasi ada di bagian berikut.

---

## 2. Inventarisasi Stack & Arsitektur

### 2.1 Stack & Versi

| Komponen | Versi Terpasang | Catatan |
|---|---|---|
| Framework | Laravel **13.23.0** (composer `^13.8`) | — |
| PHP | **8.5.0** (cosmos, `nts`→runtime ZTS) | composer minta `^8.3` (kompatibel) |
| Database | MySQL 8 (Laragon, `stockedp` / test: `stockedp_test`, 41 tabel) | strict mode ON, `utf8mb4_unicode_ci` |
| Frontend | Vue **3.5**, Vue Router 5, Pinia 4, Tailwind 4, Vite 8, axios | build via `resources/js/app.js` |
| API client | axios (SAPI), Sanctum cookie sessions | — |
| Job/queue/cache/session | `database` driver (sesuai `.env`) | lihat §7.3 |
| Realtime | Laravel Reverb (running saat audit) | dev |

### 2.2 Arsitektur

- **Feature-based modules**: tiap fitur (Inventory, Reporting, Dashboard, Replenishment, MonthEnd, MasterData, User, Auth, dll.) memegang `Routes/api.php`, Controllers, Services, Repositories, Actions.
- **Registrasi route**: `FeatureRouteServiceProvider` memuat semua file `app/Features/*/Routes/api.php` di grup `api/v1` dengan middleware `['api', 'throttle:api']`. Tidak ada `routes/api.php` global.
- **SPA fallback**: `routes/web.php` mengembalikan template `app.blade.php` (Vue SPA); handle 404 di frontend (`/:pathMatch(.*)*`).
- **Auth**: session cookie Sanctum (`statefulApi()` di `bootstrap/app.php`), tanpa token Bearer di klien.
- **Gate/permission**: `$this->authorize()` di controller master data; alias middleware `permission`; pola `PermissionCode::<X>` dari enum.

### 2.3 Konvensi yang Sudah Benar (dipertahankan)

- Aritmetika stok selalu pakai `bcmath` (presisi 4, lihat `config` `INVENTORY_QUANTITY_PRECISION=4`).
- `quantity` record movement selalu positif; arah direpresentasikan oleh `MovementType::isAddition()` (`app/Features/Inventory/Enums/MovementType.php:7-19,40-52`) → memudahkan sum/laporan.
- Mesin posting memakai `DB::transaction` + `lockForUpdate` (lihat §4.1).
- Auth anti-enumeration (pesan error generik), rate limit login 5×/60 detik.

---

## 3. Temuan Backend

### 3.1 [HIGH] Gap otorisasi pada Reporting, Dashboard, Replenishment

Hanya `ReportFilterOptionsController` yang melakukan `authorizeAnyReportPermission()`. Semua endpoint lain di area tersebut hanya middleware `auth:sanctum` **tanpa pengecekan permission**:

- `app/Features/Reporting/Controllers/*` — listing/export CSV & Excel
- `app/Features/Dashboard/Controllers/*` — tidak ada pemanggilan authorize
- `app/Features/Replenishment/Controllers/*` — `authorize()` di Request mengembalikan `true` (no-op)

**Konteks**: Untuk area master data (Product/Category/Unit/Supplier/Location/Store/Department) controller melakukan `$this->authorize('x.view')` (Gate dari enum `PermissionCode`) — ini sudah benar — tetapi hanya sebagai *controller-level check*, bukan middleware `permission:` di route.

**Dampak**: Pengguna dengan role apa pun yang berhasil login dapat mengakses laporan/data replenishment secara luas tanpa granular permission — risiko kebocoran informasi bisnis & ketidaksesuaian dengan matriks akses.

**Rekomendasi (prioritas)`**:
1. Terapkan `authorizeAnyReportPermission()` (atau middleware `permission:reports.view` dst.) pada seluruh endpoint report **dan** export, serta Dashboard.
2. Tambahkan middleware `permission:` via route (`->middleware('permission:permissions.x')`) untuk defense-in-depth, tidak hanya `$this->authorize()` di controller.
3. Pertimbangkan mencatat audit log akses laporan sensitif.

### 3.2 [MEDIUM] `StockReceiptPolicy` belum ada

Gate/policy untuk fitur otorisasi aksi domain lengkap, tetapi **policy untuk StockReceipt tidak dibuat** (policies untuk Issue/Transfer/Adjustment/Opname tersedia dan otomatis dideteksi). Akibatnya aturan bisnis aksi StockReceipt (approve/reject/cancel) tidak divalidasi lewat kebijakan sentral dan dapat berisiko aksi tanpa syarat otorisasi/status.

**Rekomendasi**: Buat `StockReceiptPolicy` (approve, reject, cancel, create, view) sesuai pola issue/transfer yang sudah ada dan daftarkan penggunaannya.

### 3.3 [MEDIUM] Handler error 500 membocorkan pesan exception mentah

`bootstrap/app.php` — exception handler mengembalikan `$exception->getMessage()` apa adanya pada kode status 500.

- Saat `APP_DEBUG=false` di production, respons tetap mengembalikan pesan internal (path file, query, driver, dll.) kepada klien.
- Tidak ada pembedaan antara `APP_DEBUG=true/false` yang menjaga kerahasiaan detail internal pada 500.

**Rekomendasi**: pada 500, kembalikan pesan generik ("Terjadi kesalahan internal") di luar environment lokal; detail cukup di log (`Log::error` + report). Khusus DB/queue error, jangan ekspos ke klien.

### 3.4 [INFO] Verifikasi autentikasi — sudah aman

- `AuthenticateUserAction`: login via email **atau** username; pesan generik ("kredensial tidak valid") → anti user-enumeration; user non-aktif mendapat pesan generik; update `last_login_at`/`last_login_ip`.
- `LoginController`: `Auth::guard('web')->login(...)`, `session()->regenerate()` setelah login, `RateLimiter::clear($throttleKey)` saat sukses, `RateLimiter::hit()` saat gagal; muat `roles.permissions` untuk sesi.
- `LoginRequest`: 5 attempts / 60 detik per `Str::transliterate(lower(login).'|'.ip)` → proteksi brute-force aktif.
- `LogoutUserAction`: `->invalidate(); ->regenerateToken();` → sesi padam aman.

### 3.5 [INFO] Mesin inventory — pondasi sudah benar

`StockMovementService::recordMultipleMovements()` (de-facto "InventoryEngine"; tidak ada kelas `*Engine*`) sudah:

- Membungkus seluruh posting dalam `DB::transaction` + `lockForUpdate` pada baris balance → konsistensi di bawah konkurensi.
- `bcadd`/`bcsub` dengan scale 4 → akurasi nilai desimal.
- Validasi stok negatif via `InsufficientStockException` (sesuai `ENABLE_NEGATIVE_STOCK=false`).
- Konstrain unik per (reference/item/location) di migrasi `2026_09_15_000001` → mencegah duplikasi posting.

Waspadai satu hal [INFO]: karena qty selalu positif dan arah ditentukan `isAddition()`, laporan yang menyimpulkan arah harus memakai `MovementType`, bukan tanda qty.

---

## 4. Temuan Database

### 4.1 [HIGH] `inventory_balances` tidak punya index pada `location_id` → full scan

- Tabel `inventory_balances` index unik komposit `prod_loc_cond_unique(product_id, location_id, condition)` dibuat di `database/migrations/2026_09_12_100003_…:21`.
- Index `idx_balances_product_id` **dihapus** di `database/migrations/2026_09_13_000001_optimize_store_and_inventory_indexes.php:26-30`.

Karena index unik ber-prefix `product_id`, **query yang menyaring `location_id` saja tidak bisa memakai index** → full scan dalam laporan.

Query terdampak:
- `app/Features/Reporting/Repositories/Eloquent/ReportingRepository.php::getPaginatedBalances` (± baris 104)
- `…::getCursorBalances` (± baris 980)
- `app/Features/Reporting/Queries/LowStockQuery.php:24-28`
- `app/Features/Dashboard/Repositories/Eloquent/OperationalDashboardRepository.php:42-73`
- Lookup balance di Replenishment

**Rekomendasi**: tambahkan migrasi index `inventory_balances(location_id)` (atau `(location_id, product_id, condition)` sesuai pola query) + analisis `EXPLAIN` untuk pola low-stock. Berlaku pula pemeriksaan index serupa pada `inventory_balances` untuk kolom `period_id`/`condition` bila dipakai sebagai filter tunggal.

### 4.2 [INFO] Konfigurasi MySQL

- `config/database.php` koneksi mysql: **`strict` mode ON**, charset `utf8mb4` / `utf8mb4_unicode_ci` → perilaku SQL ketat yang baik (mencegah silent truncation).
- Migrasi dilengkapi `optimize_store_and_inventory_indexes`, `inventory_period*`, `store_allocations`, `month_end*` — cakupan periode & snapshot cukup untuk akurasi book value.

### 4.3 [INFO] Skema & konstrain

- Tanpa foreign-keys hard di sebagian besar tabel (gaya Laravel umum — integritas dijaga di lapisan aplikasi via transaksi). Konsisten; tidak dianggap temuan.

---

## 5. Temuan Frontend

### 5.1 [LOW/MEDIUM] Redirect 401 menggugurkan query & hash

`resources/js/shared/api/api_client.js:33`:

```js
window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname);
```

- Hanya `pathname` yang disimpan → **query string & hash hilang** setelah login (mis. filter report `/report?from=…` tidak kembali).
- Baris 30 mengakses `error.config.url` tanpa guard `error.config` → berpotensi crash bila error tanpa `config`.

**Rekomendasi**: simpan `window.location.pathname + window.location.search + window.location.hash` (atau cukup redirect ke `to.fullPath` — guard router §router di bawah sebenarnya sudah memakai `to.fullPath`; gunakan nilai yang sama konsisten) dan bungkus akses `error.config` dengan optional chaining.

### 5.2 [INFO] Autentikasi cookie — pola sudah benar

- `api_client.js:20-21` — `withCredentials: true` + `withXSRFToken: true`; **tidak ada** `Authorization` header / token di `localStorage` → tidak rentan XSS token theft.
- Bootstrap CSRF via `resources/js/features/auth/api/auth_api.js:8` `axios.get('/sanctum/csrf-cookie')` (baseURL `'/'`).
- Flow login: `stores/use_auth_store.js:65-93` (initialize), `:98-108` (logout); bypass admin `isAdmin` di `:37-42`.
- Router guard `resources/js/router/index.js:63-70`: inisialisasi sekali, `requiresGuest`/`requiresAuth`/`meta.permission` → solid.

### 5.3 [INFO] Layout asset

- `public/build/manifest.json` berisi 58 asset; entry `app-D6hxODcN.js` ± **443 KB** dan shared `api_client` ±118 KB.
- Route fitur reporting/auth di-*import statis* di `resources/js/router/index.js:1-16` (dieager-load di bundle utama) → potensi peningkatan *first load* dengan `import()` dinamis per route (opsional, Medium — bukan blocker).

### 5.4 [INFO] Perintah aksesibilitas/robustness frontend

- Timeout axios default 10 dtk (`VITE_API_TIMEOUT_MS`) — wajar untuk operasi berat seperti export; pastikan UI menangani timeout 408 dengan pesan jelas.
- Tidak ditemukan toggle "debug menu" (`VITE_ENABLE_DEBUG_MENU` dsb.) di `resources/js` — tidak ada risiko build mode debug bocor.

---

## 6. Testing & Keandalan Suite (BLOCKER / Critical)

### 6.1 Hasil dua run penuh — tidak deterministik

| Run | Durasi | Total | Passed | Failed | Keunikan |
|---|---|---|---|---|---|
| Run #1 (langsung, 1 proses) | 868.814 ms | 582 | 475 | **39** | kegagalan berat di area Report CSV/Phase8A1, inventory engine rollback, StockIssue |
| Run #2 (background) | 645.927 ms | 588 | 455 | **13** | kegagalan di UnitImport, ReleaseDatasetIntegrity, benchmark, SlowMoving, etc. |

- **Kedua-duanya tidak hijau**, dan **set kegagalan berbeda-beda** (39 vs 13, tes yang gagal juga tidak tumpang tindih sempurna) → suite **order/state-dependent & flaky**.
- Tes yang sama dijalankan **terisolasi lulus** (contoh: `Tests\Feature\Inventory\StockIssueTest` 5/5 passed sendirian; `HealthCheckTest` passed) → pembuktian **interferensi antar-tes**, bukan bug produk.

### 6.2 Jejak kegagalan yang diamati

- `InventoryEngineTest::test_rollback_on_failure_in_multiple_movements` (`InventoryEngineTest.php:164`) — baris `stock_movements` dengan `reference_id=1` masih tersisa setelah rollback pada run penuh (indikasi sisa data dari tes sebelumnya / transaksi silang).
- `StockIssueTest::test_can_create_draft_issue` (`StockIssueTest.php:41`) — `500 Table 'stockedp_test.inventory_periods' doesn't exist` **hanya muncul saat run penuh**; isolasi passing. Menandakan skema DB test diputar ulang/diwipe di tengah run oleh entitas lain.
- Run #2: massal `Table 'stockedp_test.categories' doesn't exist / already exists`, deadlock **1213**, `table definition changed` **1412**, `prepared statement needs re-prepare` **1615** → skema yang sama (`stockedp_test`) di-WIPE ulang saat run berlangsung; konflik antar-proses test yang memakai satu DB milik bersama.
- Kelas benchmark/SLA (`Reporting\InventoryMovementPerformanceBenchmarkTest`, `Replenishment\ReplenishmentPerformanceBenchmarkTest`) dan `ReleaseDatasetIntegrityTest` sensitif terhadap environment & urutan → sangat flaky.

### 6.3 Akar masalah

1. **Satu database test bersama (`stockedp_test`) + `RefreshDatabase` (wipe+migrate ulang)** di muka setiap run — dua runner test yang bertumpuk saling merobohkan skema (1412/1615/"table doesn't exist").
2. **Tes tidak sepenuhnya self-contained**: beberapa tes meninggalkan data (movement/balance/period) yang tidak dibersihkan/di-isolate per-role, sehingga urutan eksekusi mengubah hasil.
3. **Benchmark & SLA test** mengandalkan waktu eksekusi mesin → non-deterministik di lingkungan berbeda (terutama di bawah beban paralel).
4. Durasi total >10 menit memicu window interferensi panjang.

### 6.4 Rekomendasi (untuk release)

1. **Isolasi database per-run** (CI): buat schema sementara per kandidat (mis. DB name = `stockedp_test_{PID}` atau pakai `paratest/` docker) lalu drop. **Larang** dua run PHPUnit berjalan bersamaan pada `stockedp_test` yang sama (pintu ke 1412/1615).
2. **Rapikan setiap test jadi self-contained**: gunakan `RefreshDatabase` + seeder per-test secara eksplisit, `tearDown` penghapusan data domain, dan hindari asumsi urutan.
3. **Pisahkan benchmark/SLA** ke kelompok terpisah (`@group benchmark`) dan skippable via env — jalankan terpisah dari regresi fitur.
4. **Jadikan suite hijau pada 3 run beruntun** sebagai syarat definisi-done release; catat baseline JSON (tests/passed/failed/duration) di CI.
5. Pastikan tidak ada 2 proses `php artisan test` menyentuh DB yang sama; dokumentasikan di `docs/INSTALLATION.md`.

---

## 7. Operasional & Keamanan Produksi

### 7.1 [HIGH] Konfigurasi `.env` untuk produksi belum "production-ready"

Temuan di `.env` saat ini:

| Variabel | Nilai audit | Wajib produksi |
|---|---|---|
| `APP_ENV` / `APP_DEBUG` | `local` / **`true`** | `production` / **`false`** |
| `SESSION_SECURE_COOKIE` | `false` | `true` (HTTPS) |
| `SESSION_SAME_SITE` | `lax` | `strict` (opsional, jika tidak ada kasus embedding lintas situs) |
| `CORS_ALLOWED_ORIGINS` / `SANCTUM_STATEFUL_DOMAINS` | banyak domain **LAN** | persempit ke domain publik yang diizinkan saja |
| `SESSION_DRIVER` / `CACHE_STORE` / `QUEUE_CONNECTION` | `database` | `redis` (atau minimal `database`) + worker |

Catatan kombinasi `APP_DEBUG=true` + handler 500 (`§3.3`) = kebocoran detail internal. Saat dekat rilis: tutup `APP_DEBUG`, aktifkan `SESSION_SECURE_COOKIE`, dan sempitkan CORS/stateful domains.

### 7.2 [MEDIUM] Tidak ada scheduler

`routes/console.php` hanya berisi perintah bawaan `inspire` — **tidak ada entri `Schedule::`**. Padahal domain inventory/mid-month bergantung pekerjaan terjadwal (aging transaksi, finalisasi period lock, purge snapshot/cache, prune session). Di production wajib menjalankan queue worker (`queue:work`) + `schedule:work`/cron, dan proses Reverb (realtime) dimonitor (supervisor/systemd).

### 7.3 [INFO] Driver database untuk semua state

Cache, session, queue semuanya `database` (berbagi MySQL). Cocok untuk skala kecil/menengah; untuk trafik laporan tinggi disarankan Redis (pilihan, bukan blocker).

### 7.4 [INFO] Deploy

Belum terlihat pemicu `php artisan config:cache`/`route:cache`/`optimize` di skrip deploy; di produksi wajib dijalankan setelah setiap deploy (dengan `APP_ENV=production`). Backup DB menggunakan skrip yang terdokumentasi (`docs/MYSQL_BACKUP_RESTORE.md`) — sangat baik.

---

## 8. Daftar Lengkap Temuan (Prioritas)

### Critical
- **C1 — Suite test tidak hijau & tidak deterministik** (39 & 13 failure; interferensi antar-tes; jalankan 1 proses per DB; benchmark flaky). Blocker rilis. (§6)

### High
- **H1 — Index `inventory_balances(location_id)` hilang → full scan** pada laporan/dashboard/low-stock/replenishment. (§4.1)
- **H2 — Endpoint Reporting/Dashboard/Replenishment tanpa cek permission** (hanya `auth:sanctum`). (§3.1)
- **H3 — Konfigurasi produksi belum aman**: `APP_DEBUG=true`, `SESSION_SECURE_COOKIE=false`, CORS/stateful domains terlalu luas. (§7.1)

### Medium
- **M1 — Error 500 membocorkan detail exception ke klien.** (§3.3)
- **M2 — Redirect login 401 menggugurkan query/hash; akses `error.config` tanpa guard.** (§5.1)
- **M3 — `StockReceiptPolicy` belum ada.** (§3.2)
- **M4 — Tidak ada scheduler/cron untuk pekerjaan period & maintenance; queue worker belum dikonfigurasi.** (§7.2)

### Low / Info
- **L1 — Import rute reporting/auth statis menambah bundle awal (±443 KB entry).** (§5.3)
- **L2 — Dokumen operasional (`INSTALLATION.md`, `MYSQL_BACKUP_RESTORE.md`) perlu ditambah peringatan "JANGAN jalankan 2 test runner pada DB yang sama".** (§6.4)
- **L3 — `error.config` optional-chaining & perbaikan kecil robustness frontend lain.** (§5.1)

---

## 9. Peta Perbaikan (Roadmap Sangat Singkat)

1. **Segera (pre-release):** C1 (isolasi DB + fix tes) → H2 (permission) → H3 (env) → H1 (migrasi index).
2. **Sebelum/bersamaan UAT:** M1 (handler 500) → M2 (redirect 401) → M3 (StockReceiptPolicy).
3. **Setelah go-live awal:** M4 (scheduler + queue worker) → L1–L3 (optional performance/UX).

---

## 10. Lampiran: Referensi File Kunci

| Area | Lokasi |
|---|---|
| Registrasi route & middleware | `app/Shared/Providers/FeatureRouteServiceProvider.php` |
| SPA fallback | `routes/web.php` |
| Bootstrap (statefulApi, alias, handler) | `bootstrap/app.php` |
| Mesin inventory | `app/Features/Inventory/Services/StockMovementService.php` |
| Enum arah pergerakan | `app/Features/Inventory/Enums/MovementType.php` |
| Repositori report | `app/Features/Reporting/Repositories/Eloquent/ReportingRepository.php` (getPaginatedBalances ±:104, getCursorBalances ±:980) |
| Low-stock query | `app/Features/Reporting/Queries/LowStockQuery.php:24-28` |
| Dashboard operasional | `app/Features/Dashboard/Repositories/Eloquent/OperationalDashboardRepository.php:42-73` |
| Satunya controller report berpermission | `app/Features/Reporting/Controllers/ReportFilterOptionsController.php` |
| Index inventory_balances | `database/migrations/2026_09_12_100003_…`, `2026_09_13_000001_…:26-30` |
| API client & redirect 401 | `resources/js/shared/api/api_client.js:20-21,33` |
| Router guard | `resources/js/router/index.js:63-70` |
| Auth store | `resources/js/features/auth/stores/use_auth_store.js` |
| Test rawan interferensi | `tests/Feature/Inventory/InventoryEngineTest.php` (:164), `tests/Feature/Inventory/StockIssueTest.php` (:41), benchmark & ReleaseDataset |
| Manifes build | `public/build/manifest.json` |

*Ditulis berdasarkan pemeriksaan langsung kode (README/dokumen terkait: `docs/PRD.md`, `docs/RELEASE_CHECKLIST_VERSION_1.md`, `docs/PERFORMANCE_VERSION_1.md`).*