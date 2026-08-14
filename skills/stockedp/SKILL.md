# StockEdp — AI Project Skill

Dokumen ini adalah pintu masuk konteks singkat untuk AI yang bekerja pada repository `ilikcute/StockEdp`.
Tujuannya: AI tidak perlu membaca ulang seluruh riwayat chat untuk memahami aturan, arsitektur, keputusan, dan status proyek.

## 1. Identitas Proyek

- Nama: **StockEdp**
- Produk: Web App Sistem Inventory
- Backend: Laravel
- Frontend: Vue.js 3 + Pinia
- Database: MySQL 8+
- API: REST JSON `/api/v1`
- Auth: Laravel Sanctum cookie/session
- Quantity inventory: `DECIMAL(14,4)`
- Timezone bisnis: `Asia/Jakarta`

## 2. Status Otoritatif Version 1

- Certified Version 1 SHA: `674dbf2e5b4d047fd8a67fee91a04f8caeb2b613`
- Fase 10D: **PASS WITH CLEAN INDEPENDENT FINAL RELEASE AUDIT**
- Belum deploy production
- Belum membuat Git tag
- Belum membuat GitHub Release

## Post-Version-1 Enhancements

### Fase 11A — Master Data Bulk Import
- Bulk import Categories, Units, Locations, Products via CSV UTF-8 template, validate/preview, & transactional commit.

### Fase 12A — Operational Inventory Dashboard & Computed Alert Center
- Read-only (`delta = 0`), location-scoped (`$user->getAllowedLocationIds()`), computed alerts, inventory health, operational queue, period activity event-time basis, recent activity (max 10, created_at DESC), top issued/received products (max 10, ISSUE/RECEIPT only), assignment-scoped filter options.

### Fase 12B — Barcode Scanner & Warehouse Mobile UX
- HID-first hardware support (USB/Bluetooth Keyboard Wedge scanner, manual keyboard/numpad). Zero new dependencies (`0` composer/npm packages added).
- Exact Barcode Lookup API (`GET /api/v1/products/barcode-lookup?barcode=...`) with `products.view` RBAC, leading-zero preservation (pure string), and active-only product constraint (409 on inactive, 404 on unknown). Read-only invariant (`delta = 0`).
- Frontend Barcode Scanner Architecture: `BarcodeScannerPanel.vue`, `use_inventory_barcode_scanner.js` (with rapid sequential scan queue), and `decimal_string.js` (exact 4-decimal string arithmetic without JS float/Number).
- Transaction Workflow Integrations: Receipt (+1.0000 per product+location), Issue (+1.0000 per product+location with stock check), Transfer (+1.0000 per product), Opname (product row locator/focus without auto-increment, blind count preserved, unexpected product flow integrated).
- Layering & Responsive: 0 direct business `apiClient` in touched `.vue` files. Responsif pada viewport `360x800`, `390x844`, `768x1024`, `1024x768`, `1280x800`.

### Fase 12C — Reorder & Replenishment Recommendation Center
- Live Decision Support System (`/api/v1/replenishment-recommendations`), strictly read-only (`delta = 0`), 0 persistent recommendation tables.
- Canonical low-stock query reuse (`minimum_stock > 0`, `on_hand < minimum_stock`, `gross_shortage = MAX(minimum_stock - on_hand, 0)`).
- Pending inbound tracking (`TransferStatus::SENT` destined for target location reduces net replenishment need).
- Safe internal source surplus allocation (`surplus = MAX(source_on_hand - source_min_stock, 0)`). Sources retain their minimum stock.
- Frozen location safety (frozen source warehouses excluded from allocations; frozen target marks recommendations non-actionable).
- Deterministic greedy allocation (`available_surplus DESC, location_id ASC`), location IDOR protection, string decimal safety (BCMath scale 4), and transfer form prefill ergonomics.

Catatan: commit dokumentasi setelah SHA di atas boleh membuat HEAD bergerak. Jangan menganggap perubahan dokumentasi sebagai perubahan source aplikasi. Jika source/test/migration/dependency berubah setelah baseline ini, lakukan regression baru sebelum mempertahankan status release.

## 3. Baca File Sesuai Tugas

- Database/migration/seeding/backup: `skills/stockedp/DATABASE.md`
- Backend/domain/inventory/concurrency: `skills/stockedp/BACKEND.md`
- Vue/Pinia/API/UI: `skills/stockedp/FRONTEND.md`
- Status fase/UAT/performance/release: `skills/stockedp/RELEASE.md`
- Cara AI mengerjakan perubahan/audit: `skills/stockedp/WORKFLOW.md`
- Ringkasan perjalanan Fase 1–10D: `skills/stockedp/HISTORY.md`
- Isu operasional yang masih perlu perhatian: `skills/stockedp/KNOWN_ISSUES.md`

Dokumen root tetap sumber detail utama bila dibutuhkan:
`AGENTS.md`, `PRD.md`, `ARCHITECTURE.md`, `DECISIONS.md`, `TASKS.md`, dan `docs/*`.

## 4. Aturan Non-Negotiable

1. Backend adalah sumber kebenaran untuk stok, authorization, maker-checker, location scope, dan decimal arithmetic.
2. Semua mutasi stok harus berada dalam database transaction dan menghasilkan `stock_movements`.
3. Quantity inventory tidak boleh melewati PHP float. Gunakan decimal string + BCMath/`DecimalQuantity`.
4. Frontend tidak boleh menggunakan JS `Number`, `parseFloat`, atau `toFixed` sebagai sumber perhitungan quantity inventory.
5. Vue page/component tidak boleh memanggil Axios/API bisnis secara langsung.
6. Flow frontend: Page/Component → Composable/Pinia Store → Feature API → shared `apiClient` → Laravel REST.
7. Controller backend tipis; business logic di Action/Service; query kompleks di Repository.
8. Authorization backend wajib; menyembunyikan tombol di frontend bukan security control.
9. Location scope/IDOR wajib aman pada transaksi dan reporting.
10. Maker-checker wajib dipertahankan pada Adjustment dan Stock Opname.
11. Freeze lokasi saat Stock Opname `IN_PROGRESS` wajib dipertahankan.
12. Jangan tambah dependency tanpa izin user.
13. Jangan refactor di luar scope.
14. Jangan melemahkan test untuk membuat implementasi PASS.
15. Update `DECISIONS.md` bila keputusan arsitektur/domain berubah.

## 5. Domain Inventory Inti

Movement canonical minimal:

- `RECEIPT`
- `ISSUE`
- `TRANSFER_OUT`
- `TRANSFER_IN`
- `ADJUSTMENT_IN`
- `ADJUSTMENT_OUT`
- `OPNAME_IN`
- `OPNAME_OUT`

Invariant utama:

`quantity_after = quantity_before + signed_delta`

- Stok negatif tidak diizinkan pada flow normal.
- Transfer harus menjaga konservasi quantity global.
- Transfer asal dan tujuan harus berbeda.
- Double-submit/double-transition tidak boleh membuat movement atau delta ganda.
- Failed mutation harus rollback seluruh balance/movement/status terkait.

## 6. Reporting V1

8 laporan canonical:

1. Inventory Balances
2. Low Stock
3. Stock Card
4. Stock Receipts
5. Stock Issues
6. Stock Transfers
7. Stock Adjustments
8. Stock Opnames

Semua memiliki CSV export V1 dengan UTF-8 BOM, streamed response, header Indonesia, formula-injection protection, decimal passthrough, dan location scoping.

## 7. Master Data CSV Bulk Import (Fase 11A)

Mendukung bulk import data master via file CSV UTF-8 (Excel-compatible) dengan alur Template → Validate → Preview → Commit:
- **Products**: resolusi kode kategori & satuan, barcode string, decimal minimum stock.
- **Categories**: kode unik, nama.
- **Units**: kode unik, nama, simbol.
- **Locations**: kode unik, observer locks, tanpa penugasan user otomatis.
- Dokumentasi detail: `docs/MASTER_DATA_IMPORT.md`, `skills/stockedp/BACKEND.md` (bagian 16), `skills/stockedp/FRONTEND.md` (bagian 16).

## 8. Release Database

Database operasional/release: **`stockedp`**.

Database berikut bukan production:

- `stockedp_release_uat`
- `stockedp_release_rehearsal`
- `stockedp_release_restore_test`

Jangan menjalankan `ReleaseVerificationSeeder` pada production.
Jangan menjalankan `migrate:fresh` pada release/production.
Baca `DATABASE.md` sebelum bootstrap database.

## 8. Quality Gates Default

Setelah perubahan source yang relevan jalankan serial sesuai scope:

```bash
php artisan test
php vendor/bin/pint --test
node node_modules/eslint/bin/eslint.js resources/js --max-warnings 0
npm run build
php artisan optimize
```

Untuk inventory/concurrency, jalankan focused tests yang relevan juga.

## 9. Cara Menilai Walkthrough / Klaim PASS

Jika user memberi walkthrough atau laporan audit:

- Jangan percaya label PASS begitu saja.
- Periksa source/commit/diff/evidence aktual.
- Laporkan jumlah `Critical / High / Medium / Low`.
- Gunakan `PASS`, `HOLD`, `CLOSED`, `READY` secara eksplisit.
- Jangan mengatakan test sudah dijalankan sendiri jika hanya membaca evidence.

## 10. Bahasa dan Gaya Kerja

- Bahasa utama: Indonesia.
- Jawaban teknis harus presisi, terstruktur, dan copy-paste-ready.
- Untuk prompt coding-agent, tetapkan baseline, scope, acceptance criteria, commands, dan PASS/HOLD rule.
- Jangan bertanya ulang jika fakta sudah tersedia di konteks atau repository.
