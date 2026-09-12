# Replenishment & Reorder Recommendation Center

Pusat Rekomendasi Reorder & Replenishment (Fase 12C) adalah modul **Decision Support System (DSS)** yang menganalisis kekurangan stok pada gudang target secara live, memperhitungkan transfer stok in-transit (`TransferStatus::SENT`), mengevaluasi ketersediaan surplus aman di gudang internal lain, serta menghasilkan rekomendasi transfer internal maupun reorder eksternal.

---

## 1. Prinsip Utama & Arsitektur

1. **Strictly Read-Only (Tanpa Mutasi)**:
   - Endpoint rekomendasi tidak pernah mengubah status stok, membuat draft/transaksi otomatis, atau memotong saldo (`delta = 0`).
   - Tidak menambahkan tabel persisten rekomendasi (`0` tabel baru). Mutasi fisik/dokumen tetap menjadi kewenangan operator melalui antarmuka modul Transfer Stok atau Penerimaan.
2. **Single Canonical Low Stock Query (`LowStockQuery`)**:
   - Berbagi satu query domain kanonikal `App\Features\Reporting\Queries\LowStockQuery::forLocation()` antara modul Reporting (`/api/v1/reports/low-stock`) dan modul Replenishment.
   - Invarian mutlak: `products.is_active = true`, `products.minimum_stock > 0`, `on_hand = COALESCE(inventory_balances.quantity, 0.0000)`, `on_hand < minimum_stock`.
   - `gross_shortage_quantity = shortage_quantity = GREATEST(products.minimum_stock - on_hand, 0.0000)`.
   - Produk tanpa baris saldo di `inventory_balances` dianggap memiliki `on_hand = 0.0000` dan `gross_shortage = minimum_stock`.
3. **Pending Inbound (Transfer In-Transit)**:
   - Hanya transfer berstatus `TransferStatus::SENT` yang dihitung sebagai barang dalam perjalanan.
   - Dokumen `DRAFT`, `RECEIVED`, atau `CANCELED` tidak dihitung sebagai pending inbound.
   - `net_replenishment_need = MAX(gross_shortage_quantity - pending_inbound_quantity, 0)`.
4. **Perlindungan Stok Minimum Gudang Sumber (Source Surplus)**:
   - Gudang sumber internal wajib mempertahankan `minimum_stock` miliknya sendiri.
   - Surplus yang aman dialokasikan: `available_surplus = MAX(source_on_hand - source_minimum_stock, 0)`.
   - Gudang dengan stok $\le$ minimum tidak akan pernah ditawarkan sebagai sumber transfer.
5. **Kesadaran Lokasi Dibekukan (Frozen Location Safety)**:
   - Gudang sumber yang berstatus `is_frozen = true` di `inventory_location_locks` langsung dikeluarkan dari daftar kandidat alokasi.
   - Jika lokasi target berstatus `is_frozen = true`, rekomendasi tetap dapat dilihat untuk perencanaan, namun `actionable = false`, `blocked_reason = 'TARGET_LOCATION_FROZEN'`, dan tombol aksi transfer dinonaktifkan.
6. **Keamanan & Pencegahan IDOR**:
   - Lokasi target wajib termasuk dalam `user->getAllowedLocationIds()`, jika tidak mengembalikan `403 Forbidden`.
   - Lokasi sumber hanya mengambil gudang yang diizinkan untuk user aktif; gudang rahasia/tidak diizinkan tidak akan pernah bocor namanya maupun surplusnya.
7. **Algoritma Alokasi Deterministik**:
   - Kandidat gudang sumber diurutkan secara greedy: `available_surplus DESC`, kemudian `location_id ASC`.
8. **Tipe Rekomendasi (Recommendation Types)**:
   - `INBOUND_COVERED`: Kebutuhan tertutup penuh oleh transfer SENT yang sedang menuju target.
   - `INTERNAL_TRANSFER`: Kebutuhan bersih dipenuhi sepenuhnya oleh surplus aman gudang internal.
   - `MIXED`: Surplus internal hanya memenuhi sebagian kebutuhan; sisanya memerlukan reorder eksternal.
   - `EXTERNAL_REORDER`: Tidak ada surplus aman di gudang internal; seluruh kebutuhan memerlukan reorder eksternal.
9. **Semantik Filter & Pagination `recommendation_type`**:
   - Kandidat base Low Stock diambil dan diperkaya dalam bulk (0 N+1 query).
   - Seluruh rekomendasi diturunkan tipe dan prioritasnya.
   - Filter `recommendation_type` diterapkan pada dataset hasil turunan, kemudian diurutkan dan dipaginasi.
   - Metadata pagination (`meta.total`, `meta.current_page`, `meta.last_page`, `meta.from`, `meta.to`) merujuk tepat pada dataset yang telah difilter tanpa menghasilkan halaman kosong/sparse.
10. **Semantik Filter Summary Card (Option A)**:
    - Ringkasan kartu metrik (`summary`) mengikuti filter basis aktif (`location_id`, `search`, `category_id`, `unit_id`, `priority`) dan menampilkan distribusi jumlah produk lintas seluruh tipe rekomendasi (`low_stock_product_count`, `inbound_covered_count`, `internal_transfer_count`, `mixed_count`, `external_reorder_count`, `critical_product_count`).
11. **Aksi Prefill Transfer & Keamanan Kuantitas**:
    - Tombol "Siapkan Transfer" membuka rute `/inventory/transfers/create` dengan query parameter (`origin_location_id`, `destination_location_id`, `product_id`, `quantity`, `source=replenishment`).
    - Kuantitas valid (cth: `2.5000`) diisi sebagai string desimal 4 digit presisi tanpa float JS.
    - Kuantitas tidak valid (cth: `abc`, `1.23456`, `-1`, `0`) **TIDAK** diubah menjadi `1.0000`, melainkan kuantitas dikosongkan dan pesan peringatan terkontrol ditampilkan: `"Rekomendasi quantity tidak valid. Silakan isi quantity secara manual."`.
    - ID lokasi atau produk yang tidak sah diabaikan secara aman dengan pesan peringatan terkontrol tanpa crash dan tanpa mutasi database.
12. **String Decimal Safety**:
    - Seluruh perhitungan aritmetika persediaan menggunakan representasi string desimal 4 digit dengan `BCMath` (`scale = 4`) tanpa konversi float PHP atau JS float.

---

## 2. Struktur API

### `GET /api/v1/replenishment-recommendations`
- **Query Parameters**:
  - `location_id` (required, int)
  - `search` (optional, string)
  - `category_id` (optional, int)
  - `unit_id` (optional, int)
  - `recommendation_type` (optional: `INBOUND_COVERED`, `INTERNAL_TRANSFER`, `MIXED`, `EXTERNAL_REORDER`)
  - `priority` (optional: `CRITICAL`, `WARNING`)
  - `sort_by` (optional: `gross_shortage_quantity`, `minimum_stock`, `on_hand_quantity`, `net_replenishment_need`, `product_name`, `sku`)
  - `sort_order` (`asc` | `desc`)
  - `per_page` (int, default 15, max 100)
  - `page` (int, default 1)
- **Response**:
  - `data`: Array rekomendasi per produk pada halaman aktif.
  - `summary`: Ringkasan metrik count produk lintas seluruh tipe rekomendasi.
  - `meta`: Metadata pagination terfilter (`current_page`, `from`, `last_page`, `per_page`, `to`, `total`).
  - `links`: Link navigasi pagination (`first`, `last`, `prev`, `next`).
  - `generated_at`: Timestamp ISO8601 komputasi live.

### `GET /api/v1/replenishment-recommendations/filter-options`
- **Response**:
  - `locations`: Daftar lokasi aktif yang dapat diakses user.
  - `categories`: Daftar kategori aktif.
  - `units`: Daftar satuan aktif.
  - `recommendation_types`: Daftar enum tipe rekomendasi beserta label.
  - `priorities`: Daftar enum prioritas beserta label.

---

## 3. Bukti Performa & Skalabilitas (Benchmark Evidence)

- **Ukuran Dataset Pengujian**:
  - Produk: 1.000 produk
  - Lokasi Gudang: 5 lokasi
  - Baris Saldo Persediaan: 5.000 saldo
  - Transaksi Transfer In-Transit (`SENT`): Representatif
- **Hasil Pengujian HTTP SLA (5 Request Beruntun)**:
  - Request 1 : ~12,40 ms (HTTP 200)
  - Request 2 : ~7,96 ms (HTTP 200)
  - Request 3 : ~8,45 ms (HTTP 200)
  - Request 4 : ~7,61 ms (HTTP 200)
  - Request 5 : ~8,35 ms (HTTP 200)
  - **MIN**: 7,61 ms | **MAX**: 12,40 ms | **AVERAGE**: 8,95 ms (SLA < 2.000 ms terpenuhi dengan margin sangat aman)
- **Query Count & N+1 Evidence**:
  - Total Query SQL untuk 50 baris rekomendasi = **Bounded (<= 25 query framework & domain, 0 N+1)**
  - Menggunakan bulk fetching untuk produk, pending inbounds, dan candidate balances.
