# Dokumentasi Fitur: Master Data Bulk Import

Fitur **Master Data Bulk Import** menyediakan fungsionalitas pengunggahan data masal berbasis file CSV (UTF-8, Excel-compatible) untuk 4 entitas master data:
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

4. **Preservasi String & Ketelitian Desimal**:
   - `barcode` dipertahankan sebagai string murni termasuk leading zeros (contoh: `000123456789`).
   - `minimum_stock` ditangani sebagai string desimal 4 digit (`0.0000`) tanpa casting float untuk menghindari rounding error.

5. **Infrastruktur Lokasi & Penguncian Stok**:
   - Import lokasi memicu `LocationObserver` secara otomatis untuk membuat row `inventory_location_locks` (`is_frozen = 0`).
   - Lokasi baru **tidak secara otomatis** ditugaskan ke user (`NEW_LOCATIONS_REQUIRE_MANUAL_ADMIN_ASSIGNMENT`).

6. **Isolasi Saldo & Mutasi**:
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
| `sku` | Ya | String(100), Upper | SKU unik produk |
| `barcode` | Tidak | String(100) | Barcode unik (string, leading zero preserved) |
| `name` | Ya | String(255) | Nama produk |
| `description` | Tidak | String | Deskripsi produk |
| `category_code` | Ya | String(50), Upper | Kode kategori yang sudah terdaftar |
| `unit_code` | Ya | String(50), Upper | Kode satuan yang sudah terdaftar |
| `minimum_stock` | Tidak | Decimal(15,4) | Minimum stok aman (default: `0.0000`) |

---

## 3. Spesifikasi API

### 1. Download Template
- **Method / URI**: `GET /api/v1/master-data-import/{type}/template`
- **Tipe didukung**: `products`, `categories`, `units`, `locations`
- **Permission**: `{type}.import`
- **Response**: `200 OK` (Attachment `Content-Type: text/csv; charset=UTF-8` dengan UTF-8 BOM).

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

## 4. Hak Akses & Keamanan

- Permission yang digunakan:
  - `products.import`
  - `categories.import`
  - `units.import`
  - `locations.import`
- Secara default, role **Admin** memiliki seluruh hak akses import.
- Petugas Gudang (Warehouse Officer) dan Supervisor tidak memiliki hak akses import secara default.
