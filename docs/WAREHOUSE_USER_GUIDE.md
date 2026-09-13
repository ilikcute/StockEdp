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
- **Master Data**: Produk, Kategori, Satuan, Supplier, Lokasi, Toko (Stores).
- **Transaksi**:
  - **Penerimaan Stok (Stock Receipts)**: Pencatatan barang masuk dari supplier / General Affair (GA).
  - **Pengeluaran Stok (Stock Issues)**: Pencatatan barang keluar untuk kebutuhan operasional.
  - **Transfer Stok (Stock Transfers)**: Pemindahan barang antarlokasi gudang/teknisi (Status: DRAFT -> SENT -> RECEIVED).
  - **Alokasi Toko (Store Allocations)**: Pencatatan penggantian unit operasional toko (pasang unit GOOD & tarik unit DEFECTIVE).
  - **Penyesuaian Stok (Stock Adjustments)**: Penyesuaian stok karena kerusakan atau temuan (Maker-Checker: disetujui Supervisor).
  - **Stock Opname**: Perhitungan fisik stok secara berkala (Snapshot -> Count -> Complete -> Review -> Post).
- **Laporan (Reports)**: Laporan Saldo Stok, Saldo Teknisi Lapangan, Histori Alokasi Toko, Stok Minimum, Kartu Stok, Laporan Transaksi, dan Ekspor CSV.

---

## 3. Alur Kerja Transaksi Utama

### A. Penerimaan Stok dari General Affair (GA Inflow)
1. Pilih menu **Transaksi > Penerimaan Stok**, klik **Buat Penerimaan Baru**.
2. Masukkan Nomor Memo / SPB GA, Tanggal Penerimaan, Lokasi Gudang Induk, Sumber Barang (`GA_PROCUREMENT` atau `GA_SERVICED`), serta daftar Produk dan Kuantitas.
3. Klik **Post** untuk memperbarui saldo stok `GOOD` secara langsung.

### B. Transfer Handshake & Retur Unit Rusak
1. **Pengiriman (*Dispatch*)**: Petugas Gudang memilih lokasi asal dan lokasi teknisi tujuan, lalu klik **Kirim**. Stok berpindah status menjadi `IN_TRANSIT`.
2. **Penerimaan (*Handshake*)**: Teknisi memeriksa fisik barang dan menekan tombol **Terima** pada perangkatnya untuk memasukkan barang ke saldo aktif lokasi teknisi.
3. **Retur Barang Rusak**: Teknisi membuat transfer dari lokasi pribadinya dengan kondisi barang `DEFECTIVE` menuju Gudang Afkir atau Gudang Induk.

### C. Alokasi Penggantian Unit Toko (Store Asset Replacement)
1. Teknisi di lokasi toko membuka menu **Alokasi Toko > Catat Penggantian**.
2. Pilih Toko Target (Kode / Nama Toko).
3. Pilih Barang Pasang (otomatis memotong stok `GOOD` milik teknisi).
4. Masukkan data Barang Tarik / Bongkar (jika ada unit rusak yang dicopot), serta alasan kerusakan. Sistem otomatis menambahkan unit bekas tersebut ke saldo `DEFECTIVE` teknisi.
5. Klik **Simpan Transaksi**. Histori pergantian unit toko langsung tercatat.

### D. Stock Opname & Penyesuaian
1. Supervisor/Petugas membuat dokumen Opname untuk lokasi gudang atau tas/kendaraan teknisi.
2. Klik **Mulai Opname** untuk snapshot kuantitas buku dan pembekuan transaksi lokasi.
3. Petugas menginput hasil perhitungan fisik (*Count*).
4. Supervisor meninjau selisih dan memposting rekonsiliasi stok.

---

## 4. Penanganan Pesan Kesalahan Umun

| Kode / Pesan Error | Penyebab | Tindakan |
| --- | --- | --- |
| **HTTP 401 (Unauthenticated)** | Sesi login habis / belum login | Silakan login kembali ke dalam sistem. |
| **HTTP 403 (Forbidden)** | Anda tidak memiliki izin hak akses | Hubungi Administrator untuk penyesuaian role/permission. |
| **HTTP 409 (Conflict)** | Transaksi sudah diposting / dalam status final | Dokumen yang sudah diposting tidak dapat diubah kembali. |
| **HTTP 422 (Unprocessable)** | Data input tidak valid / stok kurang | Periksa pesan error merah pada form (misal: stok tidak mencukupi). |
| **HTTP 429 (Too Many Requests)** | Terlalu banyak permintaan dalam waktu singkat | Tunggu beberapa detik lalu coba kembali (data form tidak akan hilang). |
