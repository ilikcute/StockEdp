# Dokumentasi Fitur: Master Data Bulk Import

Fitur **Master Data Bulk Import** menyediakan fungsionalitas pengunggahan data masal berbasis file CSV (UTF-8, Excel-compatible) untuk 4 entitas master data persediaan:
1. **Kategori (Categories)**
2. **Satuan (Units)**
3. **Lokasi (Locations)**
4. **Produk (Products)**

---

## 1. Prinsip & Kontrak Desain

1. **CREATE ONLY**:
   - Import hanya mendukung pembuatan data baru.
   - Tidak ada operasi upsert, update, replace, delete, atau sync.
   - Jika terdapat data yang sudah ada di database atau kode/SKU/barcode duplikat di dalam file, seluruh baris duplikat ditandai sebagai error.

2. **All-or-Nothing (Atomic)**:
   - Tidak ada partial import.
   - Jika terdapat 1 baris yang bermasalah dari 1.000 baris, import tidak dapat dieksekusi sampai seluruh baris diperbaiki.
   - Commit dijalankan di dalam `DB::transaction()`.

3. **Verifikasi Hash / Idempotensi Commit**:
   - Backend memverifikasi SHA256 checksum antara file yang divalidasi dan file yang dikomit.
   - Jika file mengalami modifikasi setelah validasi awal, backend menolak proses dengan HTTP 409 `FILE_CHANGED_AFTER_VALIDATION`.

4. **Preservasi String & Ketelitian Desimal Minimum Stock**:
   - `barcode` dipertahankan sebagai string murni termasuk leading zeros (contoh: `000123456789`).
   - `minimum_stock` mengikuti kontrak model Product `DECIMAL(12,2)`: dinormalisasi murni via string/BCMath (`bcadd($val, '0', 2)`) tanpa PHP float casting. Nilai dengan lebih dari 2 digit pecahan (contoh `10.5000` atau `10.501`) ditolak dengan error `INVALID_MINIMUM_STOCK`.
   - *Catatan*: Kuantitas mutasi/saldo persediaan tetap menggunakan `DECIMAL(14,4)`.

5. **Infrastruktur Lokasi & Penguncian Stok**:
   - Import lokasi memicu `LocationObserver` secara otomatis untuk membuat row `inventory_location_locks` (`is_frozen = 0`).
   - Lokasi baru **tidak secara otomatis** ditugaskan ke user (`NEW_LOCATIONS_REQUIRE_MANUAL_ADMIN_ASSIGNMENT`).

6. **Audit Fields Persistence**:
   - Product: `created_by` = current user, `updated_by` = current user.
   - Category: `created_by` = current user, `updated_by` = null pada create.
   - Unit: `created_by` = current user, `updated_by` = null pada create.
   - Location: `created_by` = current user, `updated_by` = null pada create.

7. **Isolasi Saldo & Mutasi**:
   - Master data import tidak mengubah saldo stok (`inventory_balances`) dan tidak membuat mutasi stok (`stock_movements`).

---

## 2. Struktur Kolom Template CSV

### Kategori (`template_categories.csv`)
| Kolom | Wajib | Tipe / Panjang | Keterangan |
|---|---|---|---|
| `code` | Ya | String(50), Upper | Kode kategori unik |
| `name` | Ya | String(100) | Nama kategori |
| `description` | Tidak | String | Deskripsi kategori |

### Satuan (`template_units.csv`)
| Kolom | Wajib | Tipe / Panjang | Keterangan |
|---|---|---|---|
| `code` | Ya | String(50), Upper | Kode satuan unik |
| `name` | Ya | String(100) | Nama satuan |
| `symbol` | Ya | String(20) | Simbol satuan (e.g. `pcs`, `box`, `kg`) |
| `description` | Tidak | String | Deskripsi satuan |

### Lokasi (`template_locations.csv`)
| Kolom | Wajib | Tipe / Panjang | Keterangan |
|---|---|---|---|
| `code` | Ya | String(50), Upper | Kode lokasi unik |
| `name` | Ya | String(100) | Nama lokasi |
| `description` | Tidak | String | Deskripsi lokasi |
| `address` | Tidak | String | Alamat fisik lokasi |
| `phone` | Tidak | String(50) | Nomor telepon kontak lokasi |

### Produk (`template_products.csv`)
| Kolom | Wajib | Tipe / Panjang | Keterangan |
|---|---|---|---|
| `sku` | Ya | String(50), Upper | SKU unik produk (maks 50 karakter) |
| `barcode` | Tidak | String(100) | Barcode unik (string, leading zero preserved) |
| `name` | Ya | String(150) | Nama produk (maks 150 karakter) |
| `description` | Tidak | String(2000) | Deskripsi produk (maks 2000 karakter) |
| `category_code` | Ya | String(50), Upper | Kode kategori yang sudah terdaftar |
| `unit_code` | Ya | String(50), Upper | Kode satuan yang sudah terdaftar |
| `minimum_stock` | Tidak | Decimal(12,2) | Minimum stok aman (default: `0.00`, maks 2 desimal) |

---

## 3. Spesifikasi API

### 1. Download Template
- **Method / URI**: `GET /api/v1/master-data-import/{type}/template`
- **Tipe didukung**: `products`, `categories`, `units`, `locations`
- **Permission**: `{type}.import`
- **Response**: `200 OK` (Attachment `Content-Type: text/csv; charset=UTF-8` dengan filename `template_{type}.csv` dan UTF-8 BOM).

### 2. Validasi & Preview File
- **Method / URI**: `POST /api/v1/master-data-import/{type}/validate`
- **Permission**: `{type}.import`
- **Body**: Multipart Form Data (`file`: File CSV, max 5 MB).
- **Response**: `200 OK`
  ```json
  {
    "success": true,
    "message": "Validasi file import selesai.",
    "data": {
      "type": "products",
      "file_name": "products.csv",
      "sha256": "abcdef...",
      "total_rows": 50,
      "valid_rows": 50,
      "invalid_rows": 0,
      "preview": [ ... ],
      "errors": []
    }
  }
  ```

### 3. Commit / Import Transaksional
- **Method / URI**: `POST /api/v1/master-data-import/{type}/commit`
- **Permission**: `{type}.import`
- **Body**: Multipart Form Data (`file`: File CSV, `expected_sha256`: String hex 64 karakter).
- **Response**: `201 Created`
  ```json
  {
    "success": true,
    "message": "50 Produk berhasil diimport.",
    "data": {
      "type": "products",
      "total_rows": 50,
      "imported_rows": 50,
      "failed_rows": 0
    }
  }
  ```

---

## 4. Error Code Dictionary

| Error Code | Keterangan |
|---|---|
| `REQUIRED_FIELD_MISSING` | Kolom wajib tidak diisi / kosong. |
| `FIELD_TOO_LONG` | Panjang teks melebihi batas kolom database. |
| `DUPLICATE_CODE_IN_FILE` | Kode/SKU duplikat ditemukan di baris lain dalam file CSV. |
| `DUPLICATE_CODE_IN_DB` | Kode/SKU sudah ada di database. |
| `DUPLICATE_SKU_IN_FILE` | SKU duplikat ditemukan dalam file CSV. |
| `DUPLICATE_SKU_IN_DB` | SKU sudah terdaftar di database. |
| `DUPLICATE_BARCODE_IN_FILE` | Barcode duplikat ditemukan dalam file CSV. |
| `DUPLICATE_BARCODE_IN_DB` | Barcode sudah terdaftar di database. |
| `CATEGORY_NOT_FOUND` | `category_code` tidak ditemukan di tabel kategori. |
| `UNIT_NOT_FOUND` | `unit_code` tidak ditemukan di tabel satuan. |
| `INVALID_MINIMUM_STOCK` | Format stok minimum tidak valid (harus angka desimal $\ge 0$ maks 2 angka di belakang koma). |
| `MISSING_REQUIRED_HEADER` | Header CSV tidak memiliki kolom wajib. |
| `UNKNOWN_HEADER` | Header CSV memiliki nama kolom yang tidak dikenali. |
| `DUPLICATE_HEADER` | Header CSV memiliki kolom dengan nama duplikat. |
