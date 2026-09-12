# Panduan Pengguna Petugas Gudang (Warehouse User Guide)

Dokumen ini ditujukan untuk petugas operasional gudang dan supervisor persediaan dalam mengoperasikan Sistem Inventory Version 1.

---

## 1. Login dan Hak Akses (RBAC)

1. Buka halaman utama aplikasi di alamat web yang ditentukan (misalnya `http://localhost:8000`).
2. Masukkan **Username** (atau Email) dan **Password**.
3. Sistem akan menampilkan menu sesuai hak akses yang diberikan:
   - **Petugas Gudang**: Mengakses master data (read-only), membuat draft & memposting Penerimaan / Pengeluaran / Transfer Stok, menginput fisik Stock Opname.
   - **Supervisor Inventory**: Menyetujui/memposting Stock Adjustment & Rekonsiliasi Stock Opname, serta mengekspor Laporan.

---

## 2. Navigasi Aplikasi

- **Dashboard**: Ringkasan jumlah saldo stok, barang stok minimum, dan statistik transaksi.
- **Master Data**: Produk, Kategori, Satuan, Supplier, Lokasi.
- **Transaksi**:
  - **Penerimaan Stok (Stock Receipts)**: Pencatatan barang masuk dari supplier.
  - **Pengeluaran Stok (Stock Issues)**: Pencatatan barang keluar untuk kebutuhan operasional.
  - **Transfer Stok (Stock Transfers)**: Pemindahan barang antarlokasi gudang (Status: DRAFT -> SENT -> RECEIVED).
  - **Penyesuaian Stok (Stock Adjustments)**: Penyesuaian stok karena kerusakan atau temuan (Maker-Checker: disetujui Supervisor).
  - **Stock Opname**: Perhitungan fisik stok secara berkala (Snapshot -> Count -> Complete -> Review -> Post).
- **Laporan (Reports)**: Laporan Saldo Stok, Stok Minimum, Kartu Stok, Laporan Transaksi, dan Ekspor CSV.

---

## 3. Alur Kerja Transaksi Utama

### A. Penerimaan Stok (Stock Receipt)
1. Pilih menu **Transaksi > Penerimaan Stok**, klik **Buat Penerimaan Baru**.
2. Pilih Supplier, Tanggal Penerimaan, Lokasi Gudang, serta daftar Produk dan Kuantitas.
3. Simpan sebagai **Draft** jika belum final, atau klik **Post** untuk memperbarui saldo stok secara langsung.

### B. Pengeluaran Stok (Stock Issue)
1. Pilih menu **Transaksi > Pengeluaran Stok**, klik **Buat Pengeluaran Baru**.
2. Pilih Tujuan Pengeluaran, Tanggal, Lokasi Gudang, Produk, dan Kuantitas.
3. Klik **Post**. *Sistem akan otomatis menolak transaksi jika kuantitas barang yang dikeluarkan melebihi saldo stok yang tersedia.*

### C. Transfer Stok (Stock Transfer)
1. Pilih menu **Transaksi > Transfer Stok**, klik **Buat Transfer Baru**.
2. Pilih Lokasi Asal dan Lokasi Tujuan (lokasi asal dan tujuan tidak boleh sama).
3. Klik **Kirim (Send)** untuk menandai status *IN_TRANSIT* (`TRANSFER_OUT`).
4. Pada lokasi penerima, klik **Terima (Receive)** untuk menyelesaikan transfer (`TRANSFER_IN`).

### D. Stock Opname
1. Supervisor/Petugas membuat dokumen Opname untuk lokasi tertentu.
2. Klik **Mulai Opname** untuk melakukan snapshot kuantitas buku dan pembekuan transaksi lokasi.
3. Petugas menginput hasil perhitungan fisik (*Count*).
4. Klik **Selesaikan Perhitungan (Complete)**.
5. Supervisor melakukan peninjauan variansi (surplus/shortage) dan klik **Post** untuk menyelaraskan saldo fisik dengan buku.

---

## 4. Penanganan Pesan Kesalahan Umun

| Kode / Pesan Error | Penyebab | Tindakan |
| --- | --- | --- |
| **HTTP 401 (Unauthenticated)** | Sesi login habis / belum login | Silakan login kembali ke dalam sistem. |
| **HTTP 403 (Forbidden)** | Anda tidak memiliki izin hak akses | Hubungi Administrator untuk penyesuaian role/permission. |
| **HTTP 409 (Conflict)** | Transaksi sudah diposting / dalam status final | Dokumen yang sudah diposting tidak dapat diubah kembali. |
| **HTTP 422 (Unprocessable)** | Data input tidak valid / stok kurang | Periksa pesan error merah pada form (misal: stok tidak mencukupi). |
| **HTTP 429 (Too Many Requests)** | Terlalu banyak permintaan dalam waktu singkat | Tunggu beberapa detik lalu coba kembali (data form tidak akan hilang). |
