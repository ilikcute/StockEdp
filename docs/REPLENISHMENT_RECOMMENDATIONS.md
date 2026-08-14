# Replenishment & Reorder Recommendation Center

Pusat Rekomendasi Reorder & Replenishment (Fase 12C) adalah modul **Decision Support System (DSS)** yang menganalisis kekurangan stok pada gudang target secara live, memperhitungkan transfer stok in-transit (`TransferStatus::SENT`), mengevaluasi ketersediaan surplus aman di gudang internal lain, serta menghasilkan rekomendasi transfer internal maupun reorder eksternal.

---

## 1. Prinsip Utama & Karakteristik

1. **Strictly Read-Only (Tanpa Mutasi)**:
   - Endpoint rekomendasi tidak pernah mengubah status stok, membuat draft/transaksi otomatis, atau memotong saldo.
   - Mutasi fisik/dokumen tetap menjadi kewenangan operator melalui antarmuka modul Transfer Stok atau Penerimaan.
2. **Kesesuaian Formula Stok Minimum (Canonical Low Stock)**:
   - Menggunakan definisi kanonikal low stock: `products.is_active = true`, `products.minimum_stock > 0`, dan `on_hand < minimum_stock`.
   - `gross_shortage_quantity = MAX(minimum_stock - on_hand, 0)`.
   - Produk tanpa baris saldo di `inventory_balances` dianggap memiliki `on_hand = 0.0000`.
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
9. **Ketiadaan Asumsi Supplier / PO Otomatis**:
   - Reorder eksternal bersifat informasional (menampilkan kuantitas yang dibutuhkan).
   - Tidak menghasilkan rekomendasi supplier palsu atau purchase order otomatis.
10. **Aksi Prefill Transfer**:
    - Tombol "Siapkan Transfer" membuka rute `/inventory/transfers/create` dengan query parameter (`origin_location_id`, `destination_location_id`, `product_id`, `quantity`, `source=replenishment`).
    - Form terisi secara aman tanpa auto-save atau mutasi database.

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
  - `sort_by` (optional: `gross_shortage_quantity`, `minimum_stock`, `on_hand_quantity`, `product_name`, `sku`)
  - `sort_order` (`asc` | `desc`)
  - `per_page` (int, default 15, max 100)
  - `page` (int, default 1)
- **Response**:
  - `data`: Array rekomendasi per produk.
  - `summary`: Ringkasan count produk (`low_stock_product_count`, `inbound_covered_count`, `internal_transfer_count`, `mixed_count`, `external_reorder_count`, `critical_product_count`).
  - `meta`: Metadata pagination.
  - `generated_at`: Timestamp ISO8601 komputasi live.

### `GET /api/v1/replenishment-recommendations/filter-options`
- **Response**:
  - `locations`: Daftar lokasi aktif yang dapat diakses user.
  - `categories`: Daftar kategori aktif.
  - `units`: Daftar satuan aktif.
  - `recommendation_types`: Daftar enum tipe rekomendasi beserta label.
  - `priorities`: Daftar enum prioritas beserta label.
