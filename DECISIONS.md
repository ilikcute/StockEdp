# Catatan Keputusan Arsitektur

Dokumen ini mencatat keputusan teknis dan bisnis yang sengaja dipilih dalam
pengembangan Sistem Inventory.

Tambahkan catatan baru ketika membuat keputusan yang kemungkinan dapat
dipertanyakan, diubah, atau dianggap perlu diperbaiki oleh pengembang lain
maupun AI pada masa mendatang.

Keputusan terbaru harus diletakkan paling atas.

---

## Format Keputusan

## [YYYY-MM-DD] — [Judul singkat]

**Keputusan:**  
[Jelaskan keputusan yang dipilih.]

**Alasan:**  
[Jelaskan alasan sebenarnya, termasuk batasan yang memengaruhi keputusan.]

**Alternatif yang ditolak:**  
[Jelaskan alternatif yang dipertimbangkan dan alasan tidak dipilih.]

**Tinjau kembali jika:**  
[Jelaskan kondisi yang menyebabkan keputusan perlu dievaluasi ulang.]

## 2026-08-03 — Keputusan Desain Laporan Transaksi Backend & Lock Order (Fase 8A2)

**Keputusan:**

### 1. Endpoint Canonical & Permission Laporan Transaksi
1. Canonical endpoints laporan transaksi persediaan:
   - `GET /api/v1/reports/stock-receipts` (`reports.stock_receipts.view`)
   - `GET /api/v1/reports/stock-issues` (`reports.stock_issues.view`)
   - `GET /api/v1/reports/stock-transfers` (`reports.stock_transfers.view`)
   - `GET /api/v1/reports/stock-adjustments` (`reports.stock_adjustments.view`)
   - `GET /api/v1/reports/stock-opnames` (`reports.stock_opnames.view`)
2. Seluruh endpoint bersifat **Read-Only** (`GET`), terotorisasi via PermissionCode granular, dan menerapkan lokasi scoping eksplisit via `$allowedLocationIds`.

### 2. Lock Ordering Global & Penanganan Deadlock Transient
3. Urutan lock transaksional mutasi persediaan ditegakkan secara atomik:
   $$\text{Step 1: Lock Location Locks} \rightarrow \text{Step 2: Lock Document Header} \rightarrow \text{Step 3: Lock Balances} \rightarrow \text{Step 4: Record Movement}$$
4. Penguncian lokasi (`inventory_location_locks`) dieksekusi secara deterministic `location_id ASC` di dalam transaksi aktif.
5. Transaksi posting dokumen membungkus operasi dengan `DB::transaction(..., 5)` untuk menangani potensi MySQL deadlock (error 1213 / 40001) secara otomatis.

### 3. Kontrak Strict Decimal Quantity & Penolakan Float Runtime
6. Class helper shared `App\Features\Reporting\Helpers\DecimalQuantity` menggunakan signature runtime guard `normalize(mixed $value): string`.
7. PHP `float`, `int`, `bool`, `array`, `object`, string kosong `""`, dan whitespace-only string dilarang keras dan ditolak secara runtime (melempar `TypeError` atau `InvalidArgumentException`).
8. Hanya `null` dan decimal string valid yang diterima. `null`, `"-0"`, `"-0.0000"` dinormalisasi secara presisi menjadi `"0.0000"`.
9. Status administratif Fase 8A2 berada pada `HOLD` menunggu Final Decimal Re-audit.

---

## 2026-08-03 — Keputusan Desain Reporting & Stock Card (Fase 8A1)

**Keputusan:**

### 1. Struktur Modul & Canonical Endpoints
1. Modul backend ditempatkan di `app/Features/Reporting` sesuai konvensi `ARCHITECTURE.md`.
2. Endpoint canonical Fase 8A1:
   - `GET /api/v1/reports/inventory-balances` (Laporan Saldo Stok Terkini)
   - `GET /api/v1/reports/low-stock` (Laporan Stok Minimum)
   - `GET /api/v1/reports/stock-card` (Laporan Kartu Stok)
3. Controller bersifat tipis; business logic dipisahkan ke Query Services, Read Repositories, DTOs, dan API Resources. Controller dan API Resource tidak melakukan query DB atau perhitungan bisnis langsung.

### 2. Otorisasi & Scoping Lokasi (Tanpa auth() di Repository)
4. Permission granular disatukan di `PermissionCode`:
   - `PermissionCode::REPORTS_INVENTORY_BALANCE_VIEW` (`reports.inventory_balance.view`)
   - `PermissionCode::REPORTS_LOW_STOCK_VIEW` (`reports.low_stock.view`)
   - `PermissionCode::REPORTS_STOCK_CARD_VIEW` (`reports.stock_card.view`)
5. Location scoping dikirim secara **eksplisit** dari Controller/FormRequest ke Repository sebagai `$allowedLocationIds`. Repository tidak boleh memanggil helper `auth()` secara tersembunyi.
6. Jika `$allowedLocationIds` kosong (user tidak memiliki lokasi yang diizinkan), repository langsung mengembalikan paginasi/list kosong dan summary bernilai nol tanpa mengeksekusi query database tanpa filter scope.
7. Jika user meminta `location_id` di luar `$allowedLocationIds`, sistem menolak akses dengan `403 Forbidden` atau menyaringnya ke set kosong secara aman.

### 3. Ledger Ordering & Penanganan Backdated Document Date
8. `occurred_at` pada `stock_movements` berasal dari tanggal dokumen bisnis (misal penerimaan/pengeluaran/adjustment) dan **dapat di-backdate**.
9. Namun, urutan mutasi saldo `quantity_before` $\rightarrow$ `quantity_after` dihitung secara atomik saat posting berbasis urutan `id ASC` pada `stock_movements`.
10. Oleh karena itu, **`id ASC` (atau `occurred_at ASC, id ASC`) adalah urutan ledger balance otoritatif**. Opening balance dan Closing balance pada Kartu Stok dihitung dan diurutkan menggunakan urutan ledger yang sama.
11. Tanggal dokumen bisnis (`occurred_at`) dan timestamp posting (`created_at`) disajikan sebagai kolom terpisah pada Kartu Stok.

### 4. Direction & Penanganan REVERSAL
12. Arah mutasi (`direction`) dan kuantitas masuk/keluar dihitung secara otoritatif dari selisih saldo:
    $$\text{delta} = \text{quantity\_after} - \text{quantity\_before}$$
    - `delta > 0` $\rightarrow$ `direction = IN`, `quantity_in = delta`, `quantity_out = 0.0000`
    - `delta < 0` $\rightarrow$ `direction = OUT`, `quantity_in = 0.0000`, `quantity_out = abs(delta)`
    - `delta = 0` $\rightarrow$ `direction = NONE`, `quantity_in = 0.0000`, `quantity_out = 0.0000`
13. Untuk movement jenis `REVERSAL` atau pergerakan khusus lainnya, `delta` menjadi sumber kebenaran tunggal direction dan quantity di backend (frontend tidak menentukan direction).

### 5. Aturan Low Stock Report
14. `products.minimum_stock` diperlakukan sebagai threshold per lokasi untuk V1.
15. Parameter `location_id` **wajib** pada `GET /api/v1/reports/low-stock` dan lokasi tersebut harus termasuk dalam `$allowedLocationIds` pengguna.
16. Kriteria low stock adalah `on_hand_quantity < minimum_stock` (kurang dari minimum_stock).
17. Produk dengan `minimum_stock = 0.00` tidak dianggap low stock.
18. Produk aktif tanpa saldo di lokasi terkait diperlakukan sebagai `on_hand_quantity = 0.0000` (menggunakan `LEFT JOIN` yang aman).
19. `shortage_quantity` dihitung di SQL / BCMath backend: `max(minimum_stock - on_hand_quantity, 0)` sebagai decimal string presisi 4 digit.

### 6. Semantik Tanggal & Timezone
20. Filter tanggal menggunakan format `Y-m-d` dengan timezone `Asia/Jakarta`.
21. Backend membentuk half-open interval: `occurred_at >= start_date 00:00:00` AND `occurred_at < (end_date + 1 day) 00:00:00`.
22. Rentang tanggal maksimal untuk Kartu Stok adalah 366 hari.

**Alasan:**  
Memisahkan urutan ledger berbasis `id` memastikan histori saldo pada Kartu Stok selalu konsisten secara matematis dengan `quantity_before` dan `quantity_after`, bahkan jika dokumen di-backdate. Location scoping yang dikirim eksplisit ke Repository mencegah kebocoran data dan mempermudah pengujian terisolasi tanpa session authentication global.

---

## 2026-08-01 — Keputusan Desain Freeze Infrastructure & Lock Ordering Global (Fase 7A)

**Keputusan:**

### 1. Freeze Infrastructure
1. Tabel `inventory_location_locks` digunakan sebagai infrastruktur terpusat untuk membekukan mutasi stok pada satu atau beberapa lokasi.
2. Structure `inventory_location_locks`: `location_id` (PK, FK `locations`), `is_frozen` (boolean, default false), `frozen_by_opname_id` (nullable unsignedBigInteger, FK `stock_opnames`), `frozen_at` (nullable timestamp), `created_at`, `updated_at`.
3. Check Constraint (MySQL 8.0+): `(is_frozen = 0 AND frozen_by_opname_id IS NULL AND frozen_at IS NULL) OR (is_frozen = 1 AND frozen_by_opname_id IS NOT NULL AND frozen_at IS NOT NULL)`.
4. Untuk Location baru yang belum memiliki lock row, `InventoryFreezeService` secara otomatis mengeksekusi `insertOrIgnore` concurrency-safe sebelum penguncian row dilakukan.

### 2. Lock Ordering Global (Mencegah Deadlock)
5. Seluruh transaksi mutasi stok (Receipt Post, Issue Post, Transfer Send/Receive, Adjustment Post, dan Stock Opname) **WAJIB** mengikuti urutan penguncian terpusat di `StockMovementService`:
   - Step 1: Lock dokumen transaksi (misal `StockReceipt`, `StockIssue`, `StockTransfer`, `StockAdjustment`).
   - Step 2: Extract seluruh `location_id` unik dari DTO/item, urutkan secara ascending `sort($locationIds)`.
   - Step 3: Panggil `InventoryFreezeService::lockAndValidateLocations($sortedLocationIds, $opnameContextId)`.
   - Step 4: Panggil `InventoryBalanceRepository::lockBalancesForUpdate($sortedProductLocationPairs)`.
   - Step 5: Catat movement dan update balance dalam transaction yang sama.
6. Untuk transaksi multi-lokasi (seperti Transfer Stok), `origin_location_id` dan `destination_location_id` **selalu diurutkan ascending** sebelum dikunci. Jangan pernah mengunci origin lalu destination berdasarkan urutan input.

### 3. Penanganan Transaksi Pada Frozen Location
7. Transaksi biasa (Receipt, Issue, Transfer, Adjustment) yang mencoba mengubah stok pada lokasi yang dibekukan (`is_frozen = 1`) akan ditolak dengan `DomainException(message, status: 409, errors: ['code' => 'LOCATION_FROZEN'])`.
8. Penolakan 409 `LOCATION_FROZEN` tidak mengubah saldo stok, tidak membuat pergerakan stok, dan status dokumen tetap pada DRAFT/SENT (bisa dicoba kembali setelah opname selesai).
9. Hanya transaksi Opname yang memegang kepemilikan freeze (`frozen_by_opname_id == $opnameId`) yang diizinkan melakukan mutasi stok rekonsiliasi pada lokasi tersebut.
10. Pelepasan freeze (`unfreeze`) hanya dapat dilakukan secara atomic oleh owner opname yang sama dalam satu transaksi database.

**Alasan:**  
Pengecekan freeze terpusat di `StockMovementService` menjamin bahwa tidak ada transaksi mutasi persediaan apapun yang lolos dari pembekuan, baik transaksi langsung maupun transaksi baru yang ditambahkan di kemudian hari. Penguncian lokasi dan saldo yang terurut secara global (`ascending`) secara matematis mencegah terjadinya MySQL deadlock antar transaksi yang berlawanan arah.

---

## 2026-08-01 — Keputusan Desain Stock Adjustment (Fase 6)

**Keputusan:**

### State Transition
1. Status adjustment menggunakan: `DRAFT`, `POSTED`, `CANCELED`.
2. Tidak ada approval formal, `SUBMITTED`, `APPROVED`, `REJECTED`.
3. `POSTED` dan `CANCELED` bersifat immutable. Tidak ada perubahan status setelah keduanya.
4. Koreksi atas dokumen yang sudah POSTED dilakukan dengan membuat dokumen adjustment baru.
5. Reversal tidak masuk scope Fase 6.
6. Tidak ada endpoint DELETE. Draft yang tidak terpakai dibatalkan via Cancel Action.
7. Cancel mempertahankan header dan items (hanya mengisi `canceled_by` dan `canceled_at`).

### Maker–Checker Ringan
8. `posted_by` wajib berbeda dari `created_by`. Pembuat adjustment tidak boleh mem-posting adjustment miliknya sendiri.
9. Administrator, Supervisor Inventory, dan semua role tetap mengikuti aturan maker–checker ini. Tidak ada bypass tersembunyi.
10. Jika hanya ada satu pengguna aktif, adjustment tidak dapat diposting.

### Role dan Ownership
11. **Petugas Gudang:** boleh `view` (Location yang diizinkan), `create` draft, `update` draft miliknya sendiri, `cancel` draft miliknya sendiri. Tidak boleh `post`.
12. **Supervisor Inventory:** boleh `view` (Location yang diizinkan), `create` draft, `update`/`cancel` draft pada Location yang diizinkan, `post` draft yang dibuat pengguna **lain**. Tidak boleh post draft miliknya sendiri.
13. **Administrator:** mengikuti permission dan Location access yang diberikan, tetapi tetap tidak boleh mem-posting dokumennya sendiri.

### Direction dan Quantity
14. `direction` disimpan di header dokumen: `INCREASE` atau `DECREASE`. Satu dokumen tidak boleh mencampur direction.
15. `quantity` item selalu positif (unsigned). Movement type ditentukan server berdasarkan direction:
    - `INCREASE` → `ADJUSTMENT_IN`
    - `DECREASE` → `ADJUSTMENT_OUT`
16. Frontend tidak boleh mengirim movement type.
17. Quantity disimpan sebagai `decimal(14,4)` — identik dengan `inventory_balances.quantity`.

### Reason Code dan Compatibility
18. Reason code menggunakan PHP Enum `AdjustmentReason`: `FOUND`, `DAMAGED`, `EXPIRED`, `RECORDING_ERROR`, `ADMINISTRATIVE`, `LOST`, `OTHER`.
19. Aturan compatibility reason–direction ditegakkan di backend:
    - `FOUND` → hanya `INCREASE`
    - `DAMAGED` → hanya `DECREASE`
    - `EXPIRED` → hanya `DECREASE`
    - `LOST` → hanya `DECREASE`
    - `RECORDING_ERROR` → `INCREASE` atau `DECREASE`
    - `ADMINISTRATIVE` → `INCREASE` atau `DECREASE`
    - `OTHER` → `INCREASE` atau `DECREASE`, dan `notes` wajib diisi
20. Jika `reason_code = OTHER`, `notes` wajib diisi. Whitespace-only dianggap kosong.

### Adjustment Date
21. `adjustment_date` wajib diisi dan tidak boleh berada di masa depan berdasarkan timezone `Asia/Jakarta`.
22. `posted_at` menyimpan waktu aktual posting, bukan adjustment_date.

### Schema dan Business Movement Uniqueness
23. Format nomor dokumen: `ADJ-YYYYMM-XXXX`.
24. Unique constraint `(reference_type, reference_id, product_id, location_id)` ditambahkan ke `stock_movements` untuk mencegah duplikasi business movement pada level database. Constraint ini aman untuk Receipt, Issue, dan Transfer karena:
    - Receipt/Issue: product unik per dokumen (sudah ada `UNIQUE` di items).
    - Transfer: TRANSFER_OUT dan TRANSFER_IN memiliki `location_id` berbeda.
25. Number generator menggunakan pola Fase 5: `lockForUpdate` + retry khusus MySQL error 1062 pada `adjustment_number`. Retry lainnya tidak di-catch.

### Authorization
26. Authorization diterapkan di Policy, Form Request, Repository scope (Location filter dikirim eksplisit, tidak menggunakan `auth()` langsung di Repository), dan setiap Action.
27. Jika allowed Location kosong, list harus kosong (bukan query tanpa scope).
28. Permission granular: `stock_adjustments.view`, `.create`, `.update`, `.post`, `.cancel`.
29. Tidak ada permission `submit`, `approve`, `reject` karena approval formal tidak masuk scope v1.

**Alasan:**  
Maker–checker ringan tanpa approval formal memberikan segregasi tugas minimum yang dapat diaudit tanpa kompleksitas state machine approval. Direction per dokumen memastikan tujuan transaksi jelas. Reason–direction compatibility mencegah entri yang secara semantik tidak masuk akal. Business movement uniqueness di level database merupakan pertahanan terakhir terhadap race condition yang lolos dari application lock.

**Alternatif yang ditolak:**  
- Approval formal (`SUBMITTED → APPROVED → POSTED`): ditolak karena menambah state machine yang tidak diperlukan v1.
- Direction per item (bukan per dokumen): ditolak karena mempersulit alasan audit — satu dokumen tidak seharusnya mencampur tujuan.
- Reason sebagai master table: ditolak karena set terbatas dan tidak membutuhkan CRUD UI.
- MySQL ENUM untuk direction/reason: ditolak karena konsistensi dengan keputusan Fase 4–5 yang menghindari MySQL ENUM.

**Tinjau kembali jika:**  
Bisnis membutuhkan approval multi-level, audit trail khusus per reason code, atau integrasi akuntansi yang memerlukan jurnal otomatis per reason.

---

## 2026-07-31 — Keputusan Desain Transfer Stok (Fase 5)

**Keputusan:**  
1. Status transfer: `DRAFT`, `SENT`, `RECEIVED`. Tidak menggunakan status partial.
2. Stok Warehouse asal berkurang dan movement `TRANSFER_OUT` dibuat ketika `SENT`.
3. Stok Warehouse tujuan bertambah dan movement `TRANSFER_IN` dibuat ketika `RECEIVED`.
4. Barang antara `SENT` dan `RECEIVED` dicatat sebagai *quantity in-transit* yang hanya terlacak via dokumen transfer (tidak membuat record saldo transit terpisah).
5. Transfer tidak dapat diterima sebagian (No Partial Receipt). Kuantitas yang diterima harus persis sama dengan kuantitas yang dikirim.
6. User pengirim wajib memiliki akses (authorization) ke Warehouse asal.
7. User penerima wajib memiliki akses (authorization) ke Warehouse tujuan.
8. Status `DRAFT` dapat diedit dan dibatalkan (`CANCELED`).
9. Status `SENT` tidak dapat diedit atau dibatalkan langsung.
10. Status `RECEIVED` bersifat final dan *immutable*. Koreksi ditunda sampai alur *reversal* diputuskan di masa depan (tidak masuk ruang lingkup Fase 5).
11. Warehouse asal dan tujuan harus berbeda.
12. Saat Receive, jika Produk dinonaktifkan (inactive) setelah transfer berstatus SENT, sistem tetap mengizinkan Receive agar barang tidak menggantung (in-transit permanen). Namun, produk nonaktif tidak bisa digunakan untuk transfer baru. Demikian pula jika Warehouse asal dinonaktifkan setelah SENT, Receive di tujuan tetap diizinkan. Warehouse tujuan harus tetap aktif.

**Alasan:**  
Pola ini merupakan bentuk paling stabil untuk transfer barang *multi-item* di mana *quantity in-transit* harus dipertanggungjawabkan tanpa membebani sistem dengan tabel saldo virtual. Sinkronisasi *out* dan *in* memastikan tidak ada barang hilang yang tidak terdeteksi. Partial receipt dan reversal dihindari pada versi awal untuk mempertahankan keandalan (*reliability*) sistem.

**Alternatif yang ditolak:**  
- Mengurangi dan menambah stok secara instan dalam 1 fase (langsung dari Warehouse A ke B). Ditolak karena tidak mencerminkan realita fisik (barang butuh waktu perjalanan).
- Membuat tabel virtual "Transit Warehouse". Ditolak karena redundan; dokumen transfer sendiri sudah memuat *in-transit state* (dokumen yang berstatus `SENT` tapi belum `RECEIVED`).
- Menerima barang parsial (sebagian). Ditolak karena menambah tingkat kompleksitas yang ekstrem untuk melacak sisa pengiriman.

**Tinjau kembali jika:**  
Ada kebutuhan mendesak dari bisnis untuk melakukan pengiriman multi-kendaraan dari satu dokumen transfer yang sama (Parsial/DO split), atau ada kebutuhan untuk melacak barang rusak selama dalam perjalanan.

**Keputusan:**  
1. Nama entitas dan tabel saldo stok menggunakan `inventory_balances`.
2. Tipe data kuantitas stok menggunakan `decimal(14,4)`.
3. Transaksi dokumen stok hanya menggunakan status `DRAFT` dan `POSTED` (pembatalan ditunda).
4. Pembuatan saldo awal: setiap kombinasi Produk dan Lokasi akan selalu memiliki baris di `inventory_balances` terlepas dari status aktifnya (akan diotomatiskan via Observer saat pembuatan).
5. Format dokumen menggunakan `REC-YYYYMM-XXXX` (Receipt) dan `ISS-YYYYMM-XXXX` (Issue).
6. Strategi Idempotency: Dokumentasi yang sudah POSTED akan menolak *request* posting berulang dengan 409 Conflict.
7. Pengeluaran stok divalidasi ketat dan dikawal DB constraint agar tidak boleh menghasilkan saldo negatif.
8. Transaksi stok multi-item di-wrap dengan `DB::transaction()` serta perlindungan *row locking* menggunakan `lockForUpdate()`.
9. Saldo yang ditarik habis (menjadi 0.0000) tetap tersimpan (row tidak dihapus).
10. Penerimaan wajib memiliki *Supplier*, pengeluaran wajib mencatat *Purpose*. Valuation (Akuntansi Stok) tidak termasuk Fase 4.

**Alasan:**  
Menjamin keamanan transaksional, rekam histori yang jelas (ledger basis), presisi tinggi perhitungan stok, serta konsistensi status inventory seperti yang disetujui pada rencana implementasi Fase 4.

**Alternatif yang ditolak:**  
- Membuat baris balance secara *lazy* (baru dibuat saat transaksi berjalan). Ditolak karena kebutuhan tracking mengharuskan semua produk, aktif maupun tidak, terdaftar pada data *balances*.
- Menerapkan akuntansi stok / *valuation*. Ditolak karena berada di luar cakupan fase 4.

**Tinjau kembali jika:**  
Sistem memerlukan mekanisme pembatalan (*reversal*), multi-status yang rumit, atau integrasi akuntansi stok keuangan (*inventory valuation*).

## 2026-07-30 — Keputusan Struktur dan Aturan Master Data (Fase 3)

**Keputusan:**  
1. Nama entitas dan endpoint lokasi menggunakan `Location` (`/api/v1/locations`).
2. Kategori (`categories`) bersifat datar (Flat Category tanpa parent-child) pada versi 1.
3. Produk (`products`) hanya mereferensikan satu satuan dasar (`unit_id`) tanpa konversi pada versi 1.
4. Barcode bersifat opsional (`nullable`), namun unik jika diisi.
5. SKU diisi secara manual, wajib, unik, dan diproses secara *case-insensitive* (dikondisikan UPPERCASE).
6. Master data yang sudah memiliki keterkaitan relasi atau transaksi dilarang dihapus permanen (`ON DELETE RESTRICT`). Penghentian penggunaan dilakukan via flag `is_active = false`.

**Alasan:**  
Mengikuti batasan PRD.md untuk menjaga kesederhanaan model data versi 1, mencegah kerusakan integritas histori transaksi, serta memastikan keunikan data master tanpa race condition.

**Alternatif yang ditolak:**  
- Menggunakan `SoftDeletes` ditolak karena berisiko konflik unique constraint pada database MySQL saat memasukkan data baru dengan kode yang sama dengan data terhapus.
- Multi-unit conversion dan hierarchical category ditolak karena berada di luar ruang lingkup versi 1.

**Tinjau kembali jika:**  
Kebutuhan bisnis v2 mengharuskan struktur kategori bertingkat, konversi satuan multi-level, atau recycle bin untuk master data.

---

## 2026-07-30 — Menggunakan Bunny Fonts sebagai penyedia font

**Keputusan:**  
Font Instrument Sans dimuat melalui Bunny Fonts menggunakan integrasi
`laravel-vite-plugin/fonts`. Ini menggantikan pendekatan Google Fonts atau
system font stack.

**Alasan:**  
Bunny Fonts merupakan alternatif Google Fonts yang ramah privasi (GDPR-compliant)
dan sudah tersedia melalui `laravel-vite-plugin`. Font Instrument Sans dipilih
karena memiliki keterbacaan yang baik untuk antarmuka berbasis data seperti
Sistem Inventory.

**Alternatif yang ditolak:**  
Google Fonts tidak dipilih karena mengirimkan data pengguna ke server Google.
System font stack tidak memberikan konsistensi visual di berbagai sistem operasi.

**Tinjau kembali jika:**  
Bunny Fonts tidak lagi tersedia, font Instrument Sans kurang sesuai untuk
antarmuka Bahasa Indonesia, atau ada kebijakan font yang mengharuskan self-hosting.

---

## 2026-07-30 — Menggunakan TailwindCSS sebagai framework CSS

**Keputusan:**  
TailwindCSS v4 digunakan sebagai framework CSS utama melalui plugin Vite
`@tailwindcss/vite`. Keputusan ini menggantikan pendekatan Vanilla CSS yang
disebutkan dalam AGENTS.md.

**Alasan:**  
TailwindCSS sudah tersedia dalam konfigurasi proyek awal. Penggunaan utility
classes mempercepat pengembangan antarmuka tanpa harus mendefinisikan class CSS
kustom untuk setiap komponen. TailwindCSS v4 bekerja langsung dengan Vite
tanpa file konfigurasi tambahan.

**Alternatif yang ditolak:**  
Vanilla CSS tidak dipilih karena memerlukan lebih banyak kode boilerplate dan
waktu lebih lama untuk menghasilkan antarmuka yang konsisten. Vanilla CSS tetap
boleh digunakan untuk kasus yang tidak dapat diekspresikan dengan utility class.

**Tinjau kembali jika:**  
TailwindCSS menyebabkan konflik dengan komponen UI pihak ketiga, bundle CSS
menjadi terlalu besar, atau tim memutuskan untuk menggunakan component library
yang memiliki sistem styling sendiri.

---

## 2026-07-30 — Stock movement yang sudah diposting tidak boleh dihapus

**Keputusan:**  
Stock movement yang sudah berhasil diposting tidak boleh diubah atau dihapus
secara permanen. Kesalahan transaksi diperbaiki menggunakan reversal atau
stock adjustment baru.

**Alasan:**  
Riwayat perubahan stok harus dapat ditelusuri. Mengubah atau menghapus movement
lama dapat menyebabkan saldo stok tidak sesuai dengan histori transaksi dan
menyulitkan proses audit.

**Alternatif yang ditolak:**  
Mengizinkan pengguna mengedit atau menghapus stock movement lama. Pendekatan
ini lebih sederhana, tetapi dapat merusak integritas histori stok.

**Tinjau kembali jika:**  
Aplikasi memiliki mekanisme event sourcing atau versioning yang dapat menjamin
seluruh perubahan data tetap tercatat dan dapat diaudit.

---

## 2026-07-30 — Stok negatif dinonaktifkan secara default

**Keputusan:**  
Sistem menolak transaksi stok keluar apabila quantity yang tersedia tidak
mencukupi. Stok negatif hanya dapat digunakan jika nantinya tersedia konfigurasi
bisnis khusus yang mengizinkannya.

**Alasan:**  
Sistem Inventory membutuhkan saldo stok yang dapat dipercaya. Stok negatif
sering menunjukkan transaksi terlambat dicatat, kesalahan input, atau proses
penerimaan barang yang belum selesai.

**Alternatif yang ditolak:**  
Selalu mengizinkan stok negatif agar transaksi tidak terhambat. Pendekatan ini
berisiko menyembunyikan masalah operasional dan menghasilkan laporan stok yang
tidak akurat.

**Tinjau kembali jika:**  
Operasional perusahaan mengharuskan transaksi penjualan atau pengeluaran barang
tetap berjalan sebelum penerimaan barang dicatat.

---

## 2026-07-30 — Perubahan stok wajib menggunakan database transaction

**Keputusan:**  
Setiap penerimaan, pengeluaran, transfer, adjustment, dan rekonsiliasi stock
opname harus diproses dalam database transaction.

Untuk transaksi yang dapat mengubah saldo stok secara bersamaan, backend harus
menggunakan row locking pada data saldo yang terkait.

**Alasan:**  
Pembaruan saldo stok dan pembuatan stock movement merupakan satu kesatuan.
Seluruh perubahan harus dibatalkan apabila salah satu proses gagal. Row locking
diperlukan untuk mencegah race condition ketika beberapa pengguna mengubah stok
yang sama secara bersamaan.

**Alternatif yang ditolak:**  
Memperbarui saldo dan mencatat movement melalui query terpisah tanpa transaction.
Pendekatan tersebut dapat menghasilkan saldo tanpa histori atau histori tanpa
saldo apabila salah satu query gagal.

**Tinjau kembali jika:**  
Arsitektur berpindah ke event sourcing, distributed transaction, atau sistem
inventory dengan pemrosesan asynchronous yang memiliki mekanisme konsistensi
tersendiri.

---

## 2026-07-30 — Backend menjadi sumber kebenaran data stok

**Keputusan:**  
Laravel dan MySQL menjadi sumber kebenaran untuk saldo stok, ketersediaan barang,
dan validitas transaksi inventory.

Frontend dan Pinia hanya menampilkan state serta mengirimkan perintah transaksi.
Frontend tidak menentukan hasil akhir perubahan stok.

**Alasan:**  
Beberapa pengguna dapat melakukan transaksi secara bersamaan. Perhitungan yang
hanya dilakukan di frontend dapat menggunakan data lama dan menyebabkan selisih
stok.

**Alternatif yang ditolak:**  
Menghitung dan memperbarui saldo akhir di frontend sebelum dikirim ke backend.
Pendekatan ini rentan terhadap manipulasi, data kedaluwarsa, dan race condition.

**Tinjau kembali jika:**  
Aplikasi dikembangkan menjadi offline-first dan membutuhkan transaksi lokal
sementara dengan mekanisme sinkronisasi serta resolusi konflik.

---

## 2026-07-30 — Pinia digunakan untuk shared frontend state

**Keputusan:**  
Pinia digunakan untuk state frontend yang dipakai oleh beberapa komponen atau
halaman, seperti data pengguna, filter, pagination, loading, error, dan cache
data fitur.

State lokal sebuah form atau komponen tetap berada di component atau composable.

**Alasan:**  
Pinia merupakan state management resmi dan sesuai dengan Vue 3. Pemisahan state
lokal dan shared state menjaga store tetap kecil serta mudah dipelihara.

**Alternatif yang ditolak:**  
Menyimpan seluruh state aplikasi dalam satu global store. Pendekatan tersebut
menciptakan ketergantungan antarmodul dan membuat perubahan state sulit
ditelusuri.

Vuex juga tidak dipilih karena Pinia lebih sesuai untuk pengembangan Vue 3.

**Tinjau kembali jika:**  
Pinia tidak lagi didukung oleh ekosistem Vue atau kebutuhan aplikasi berubah
menjadi sangat sederhana sehingga shared state tidak lagi diperlukan.

---

## 2026-07-30 — Frontend berkomunikasi melalui REST API JSON

**Keputusan:**  
Vue berkomunikasi dengan Laravel menggunakan REST API dengan format JSON dan
prefix versi `/api/v1`.

**Alasan:**  
REST API mudah diuji, dapat digunakan oleh frontend terpisah, dan memungkinkan
backend dikembangkan untuk integrasi aplikasi lain pada masa mendatang.

Versioning endpoint diperlukan agar perubahan API berikutnya tidak langsung
merusak frontend yang sudah berjalan.

**Alternatif yang ditolak:**  
Menggunakan Blade sebagai UI utama. Blade dapat menyederhanakan aplikasi kecil,
tetapi tidak sesuai dengan keputusan penggunaan Vue sebagai frontend.

GraphQL belum dipilih karena menambah kompleksitas yang belum dibutuhkan oleh
Sistem Inventory.

**Tinjau kembali jika:**  
Aplikasi memiliki banyak client dengan kebutuhan data yang sangat berbeda atau
REST menghasilkan terlalu banyak endpoint dan over-fetching.

---

## 2026-07-30 — Business logic backend menggunakan Action dan Service

**Keputusan:**  
Controller hanya menangani HTTP request, authorization, validasi, pemanggilan
use case, dan HTTP response.

Satu use case ditempatkan dalam Action. Business logic yang digunakan oleh
beberapa Action ditempatkan dalam Service.

**Alasan:**  
Controller yang tipis lebih mudah diuji dan dipelihara. Action membuat setiap
use case memiliki titik masuk yang jelas, sedangkan Service mencegah duplikasi
business logic.

**Alternatif yang ditolak:**  
Menempatkan business logic langsung dalam Controller atau Model. Pendekatan
tersebut membuat HTTP layer terlalu erat dengan aturan bisnis dan menghasilkan
Model yang memiliki terlalu banyak tanggung jawab.

**Tinjau kembali jika:**  
Sebuah fitur terbukti hanya melakukan operasi CRUD sederhana tanpa business
logic dan penambahan Action tidak memberikan manfaat nyata.

---

## 2026-07-30 — Repository digunakan sebagai batas akses data

**Keputusan:**  
Query database yang berkaitan dengan use case ditempatkan di Repository.
Action dan Service dapat bergantung pada contract Repository, bukan pada detail
query database.

Query scope sederhana yang hanya berkaitan dengan satu Model tetap boleh berada
di Model Eloquent.

**Alasan:**  
Repository memberikan lokasi yang konsisten untuk query kompleks dan membantu
memisahkan business logic dari implementasi penyimpanan data.

**Alternatif yang ditolak:**  
Memanggil Eloquent secara langsung dari Controller. Pendekatan tersebut membuat
query tersebar dan menyulitkan pengujian use case.

Membungkus seluruh operasi Eloquent yang sederhana juga ditolak karena dapat
menghasilkan abstraksi berlebihan tanpa manfaat.

**Tinjau kembali jika:**  
Repository hanya menjadi pembungkus Eloquent tanpa memberikan batas,
konsistensi, atau kemudahan pengujian.

---

## 2026-07-30 — Struktur proyek menggunakan feature-first

**Keputusan:**  
Backend dan frontend disusun berdasarkan fitur atau domain bisnis.

Contoh fitur meliputi:

- Product
- Category
- Unit
- Supplier
- Warehouse
- Inventory
- StockMovement
- StockAdjustment
- StockOpname
- Reporting

Setiap fitur menyimpan controller, action, service, repository, komponen, store,
dan file terkait di dalam batas fiturnya masing-masing.

**Alasan:**  
Sistem Inventory akan berkembang berdasarkan modul bisnis. Struktur
feature-first membuat kode sebuah fitur lebih mudah ditemukan, diuji, dan
dikembangkan tanpa harus berpindah ke banyak folder global.

**Alternatif yang ditolak:**  
Struktur layer-first global seperti seluruh Controller, Service, Repository,
dan DTO ditempatkan dalam folder terpisah. Struktur tersebut mudah digunakan
pada awal proyek, tetapi menjadi sulit dinavigasi ketika jumlah fitur bertambah.

**Tinjau kembali jika:**  
Aplikasi tetap sangat kecil dan pembagian fitur menghasilkan terlalu banyak
folder tanpa memberikan batas domain yang nyata.

---

## 2026-07-30 — Kode shared harus digunakan minimal oleh dua fitur

**Keputusan:**  
Kode hanya ditempatkan dalam `app/Shared` atau `src/shared` apabila digunakan
oleh minimal dua fitur.

Helper yang hanya digunakan oleh satu fitur harus tetap berada di dalam fitur
tersebut.

**Alasan:**  
Folder shared yang tidak dikendalikan mudah berubah menjadi tempat penampungan
kode umum dan menciptakan ketergantungan tersembunyi antarfitur.

**Alternatif yang ditolak:**  
Memindahkan kode ke shared sejak pertama kali dibuat karena dianggap mungkin
digunakan kembali. Kebutuhan yang belum nyata dapat menghasilkan abstraksi yang
salah.

**Tinjau kembali jika:**  
Sebuah implementasi mulai digunakan oleh fitur kedua atau telah menjadi bagian
dari infrastruktur aplikasi secara keseluruhan.

---

## 2026-07-30 — Menggunakan MySQL sebagai database utama

**Keputusan:**  
MySQL digunakan sebagai database utama untuk master data, transaksi inventory,
saldo stok, dan stock movement.

**Alasan:**  
Data inventory bersifat relasional dan membutuhkan transaction, constraint,
index, serta row locking. MySQL didukung dengan baik oleh Laravel dan sesuai
untuk kebutuhan tersebut.

**Alternatif yang ditolak:**  
MongoDB tidak dipilih karena model data inventory memiliki relasi dan kebutuhan
konsistensi transaksi yang kuat.

SQLite tidak digunakan sebagai database produksi karena kebutuhan concurrency
dan locking transaksi inventory.

**Tinjau kembali jika:**  
Skala, kebutuhan analitik, atau arsitektur distribusi tidak lagi dapat dilayani
oleh MySQL dengan aman dan efisien.

---

## 2026-07-30 — Penyimpanan file dan web server menggunakan lingkungan lokal

**Keputusan:**  
Versi awal aplikasi dijalankan melalui web server lokal. File hasil upload
disimpan menggunakan Laravel local storage.

**Alasan:**  
Tahap awal berfokus pada pengembangan dan penggunaan dalam lingkungan lokal.
Pendekatan ini mengurangi kompleksitas deployment, cloud storage, dan biaya
infrastruktur.

**Alternatif yang ditolak:**  
Cloud hosting dan object storage belum digunakan karena belum menjadi kebutuhan
versi awal serta akan menambah konfigurasi dan biaya operasional.

**Tinjau kembali jika:**  
Aplikasi harus diakses dari luar jaringan lokal, digunakan oleh beberapa
cabang, membutuhkan high availability, atau file harus tersedia dari beberapa
server.

---

## 2026-07-30 — Realtime tidak menjadi bagian inti versi awal

**Keputusan:**  
Pinia digunakan untuk state management frontend. Laravel Reverb tidak dianggap
sebagai state management dan belum menjadi dependency wajib untuk versi awal.

Pembaruan data stok dilakukan melalui request API dan refresh data setelah
transaksi berhasil.

**Alasan:**  
Reverb merupakan layanan WebSocket untuk komunikasi realtime, bukan pengelola
state frontend. Realtime menambah kebutuhan event broadcasting, koneksi
WebSocket, authorization channel, dan penanganan reconnect.

**Alternatif yang ditolak:**  
Mengaktifkan realtime untuk seluruh perubahan data sejak awal. Kompleksitas
tersebut belum diperlukan untuk menjalankan transaksi inventory dengan benar.

**Tinjau kembali jika:**  
Beberapa terminal harus melihat perubahan stok secara langsung, dashboard
membutuhkan pembaruan otomatis, atau notifikasi transaksi harus diterima tanpa
refresh.

---

## 2026-08-03 — Keputusan Desain Reporting & Stock Card v1 (Posting Ledger Basis)

**Keputusan:**

1. **Stock Card Sebagai Posting Ledger**:
   - Stock Card pada v1 berfokus sebagai posting ledger authoritative (waktu mutasi saldo dilakukan), bukan historical effective-date ledger.
   - Urutan pergerakan stok: `created_at ASC, id ASC`.
   - Filter periode laporan: berbasis tanggal posting (`created_at`). Parameter request `start_date` dan `end_date` memfilter mutasi yang diposting dalam periode tersebut (`[start 00:00:00, (end + 1 day) 00:00:00)`).
   - Metadata response menyajikan `date_basis: "POSTED_AT"`.
2. **Representasi Tanggal**:
   - `occurred_at`: Disajikan sebagai `document_date` / `occurred_at` (tanggal dokumen transaksi).
   - `created_at`: Disajikan sebagai `posted_at` (timestamp saat mutasi diposting).
   - `movement_sequence`: Berasal dari `id` mutasi.
3. **Penyajian Balance Chain**:
   - Menggunakan urutan `created_at ASC, id ASC`, setiap baris mutasi persediaan selalu memenuhi invariant `row[n].quantity_after == row[n+1].quantity_before`.
   - Transaksi backdated (tanggal dokumen di masa lalu) diposting pada saat ini, sehingga tampil sesuai urutan posting (`created_at`) dan tidak memutus kontiguitas saldo mutasi.
4. **Indeks Database**:
   - Menambahkan indeks aditif `idx_stock_card_posted` (`product_id`, `location_id`, `created_at`, `id`) pada `stock_movements`.
   - Indeks `idx_stock_card` (`product_id`, `location_id`, `occurred_at`, `id`) tetap dipertahankan untuk kebutuhan query tanggal dokumen/operational.