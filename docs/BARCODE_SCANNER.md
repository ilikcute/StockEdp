# Dokumentasi Barcode Scanner & Warehouse Mobile UX (Fase 12B)

## 1. Ringkasan & Ruang Lingkup Hardware

Fitur Barcode Scanner pada Fase 12B dirancang sebagai **input accelerator** untuk mempercepat operasional pencatatan gudang tanpa mengubah state machine, invariant ledger, atau maker-checker pada transaksi persediaan.

### Scope Dukungan Hardware (HID-First):
- **USB Barcode Scanner**: Berfungsi sebagai keyboard wedge yang mengirimkan karakter barcode + tombol `Enter`.
- **Bluetooth Barcode Scanner**: Mode keyboard emulation (SPP/HID) yang mengirimkan barcode string + `Enter`.
- **Manual Keyboard / Numpad Entry**: Input manual melalui keyboard fisik atau mobile virtual keyboard.
- **Camera Scanning**: Kamera native/third-party package ditangguhkan (deferred) demi menjaga integritas zero-dependency (`0` composer/npm packages added).

---

## 2. Kontrak Data Barcode (String & Leading-Zero Safety)

- Barcode disimpan sebagai `string(100)` nullable unik pada tabel `products`.
- **Leading Zeros**: String barcode seperti `"000123456789"` tetap dipertahankan secara utuh sebagai tipe `string` dan tidak boleh di-cast ke `int`, `float`, atau `Number`.
- **Pencarian Eksak**: Lookup barcode menggunakan operator exact match (`=`), bukan `LIKE %...%` atau fuzzy match.
- **Produk Nonaktif**: Barcode dari produk yang tidak aktif (`is_active = false`) akan ditolak dengan HTTP `409 PRODUCT_INACTIVE`. Barcode yang tidak terdaftar menghasilkan HTTP `404 BARCODE_NOT_FOUND`.

---

## 3. Endpoint Barcode Lookup API

```http
GET /api/v1/products/barcode-lookup?barcode=<string>
```

- **Autentikasi & Otorisasi**: Wajib login (`auth:sanctum`) dan memiliki izin `products.view`.
- **Urutan Route**: Didaftarkan sebelum route statis `products/{product}` untuk mencegah route-model binding capturing string `'barcode-lookup'`.
- **Read-Only Invariant**: Endpoint ini 100% read-only, mutasi inventory delta = 0.

### Contoh Response Payload:
```json
{
  "success": true,
  "data": {
    "id": 15,
    "sku": "PRD-0015",
    "barcode": "000123456789",
    "name": "Aqua 600 ML",
    "is_active": true,
    "unit": {
      "id": 1,
      "name": "Pieces",
      "symbol": "PCS"
    },
    "category": {
      "id": 1,
      "name": "Minuman"
    }
  }
}
```

---

## 4. Alur Integrasi Transaksi Persediaan

### A. Stock Receipt (Penerimaan Barang)
- **Konteks**: Memerlukan pilihan *Lokasi Scan*.
- **Scan Baru**: Jika kombinasi `(product_id, location_id)` belum ada di daftar item, baris baru ditambahkan dengan kuantitas `"1.0000"`.
- **Scan Ulang (Repeated Scan)**: Jika kombinasi `(product_id, location_id)` sudah ada, kuantitas ditambah exact `+1.0000` menggunakan aritmatika string presisi tinggi (misal: `"2.5000"` + `"1.0000"` = `"3.5000"`).

### B. Stock Issue (Pengeluaran Barang)
- **Konteks**: Memerlukan pilihan *Lokasi Scan Asal*.
- **Ketersediaan Stok**: Sistem memuat saldo persediaan (`available_stock`) dari API. Jika kuantitas draft melebihi stok yang tersedia, sistem menampilkan badge peringatan stok tanpa memblokir form lokal (backend tetap menjadi otoritas validasi saat posting).
- **Scan Ulang**: Menambahkan `+1.0000` pada baris item dengan `(product_id, location_id)` yang sama.

### C. Stock Transfer (Transfer Barang)
- **Konteks**: Memerlukan pilihan *Lokasi Asal* dan *Lokasi Tujuan*.
- **Scan Ulang**: Kuantitas item produk yang sama ditambah `+1.0000` (kunci duplikasi adalah `product_id`).

### D. Stock Opname Count (Ruang Hitung Opname)
- **Blind Count Invariant**: Saldo sistem, snapshot, dan variance tetap 100% disembunyikan.
- **Locate / Focus Only**: Barcode scanning pada Stock Opname **TIDAK** menambah kuantitas secara otomatis (`≠ +1.0000`). Scan akan menyoroti baris produk dan memfokuskan field input hitung fisik agar petugas mengisi angka fisik secara sadar.
- **Produk Tak Terduga (Unexpected Product)**: Jika produk aktif yang discan tidak termasuk dalam sesi opname dan user memiliki ability `can_add_item`, modal Produk Tak Terduga dibuka dengan produk terpilih otomatis (tetap memerlukan input kuantitas manual). Jika tidak berizin, sistem menampilkan pesan error informatif tanpa mutasi data.

---

## 5. Arsitektur Frontend & Presisi Desimal

1. **`decimal_string.js`**:
   - `normalizeDecimal4String(val)`: Menghasilkan format 4-desimal standar (`DECIMAL(14,4)`).
   - `addDecimal4Strings(a, b)`: Melakukan penjumlahan menggunakan BigInt scaled arithmetic tanpa floating point arithmetic JS (`parseFloat`, `Number`, `Math.*`).
   - `compareDecimal4Strings(a, b)`: Membandingkan dua string desimal.
2. **`use_inventory_barcode_scanner.js`**:
   - Sequential scan queue untuk menangani input cepat (rapid scanning) tanpa drop data atau race condition.
3. **`BarcodeScannerPanel.vue`**:
   - Form input ramah mobile/tablet dengan touch target >= 44px, autofocus setelah scan, tombol clear, dan pencegahan submit form tidak sengaja (`event.preventDefault()`).

---

## 6. Warehouse Mobile & Accessibility UX

- **Responsive Viewport**: Diuji dan berfungsi optimal pada resolusi `360x800`, `390x844` (smartphone), `768x1024` (tablet vertikal), `1024x768`, dan `1280x800` (desktop/laptop).
- **Aksesibilitas**: Keyboard navigation penuh, label ARIA, indikator fokus jelas, dan indikator status multi-modal (teks + ikon + badge warna + haptic vibration jika didukung perangkat).
