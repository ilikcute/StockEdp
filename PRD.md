# Inventory System — PRD

**Ringkasan:** Aplikasi web untuk mencatat, mengendalikan, dan menelusuri
persediaan barang pada satu atau beberapa lokasi penyimpanan.

**Pengguna:** Petugas gudang, admin inventory, dan supervisor operasional yang
mengelola penerimaan, pengeluaran, transfer, penyesuaian, dan pemeriksaan stok.

**Indikator keberhasilan:** Pengguna dapat mencatat transaksi perubahan stok
dalam waktu kurang dari 60 detik, dan setiap perubahan saldo dapat ditelusuri
kembali ke stock movement serta pengguna yang membuatnya.

---

## Ruang Lingkup Versi 1

- Pengguna dapat mengelola produk, kategori, satuan, supplier, dan lokasi
  penyimpanan yang diperlukan untuk menjalankan transaksi inventory.
- Pengguna dapat mencatat penerimaan dan pengeluaran barang dengan nomor
  referensi, tanggal, lokasi, quantity, dan catatan transaksi.
- Pengguna dapat memindahkan barang antarlokasi dengan movement keluar dari
  lokasi asal dan movement masuk ke lokasi tujuan.
- Pengguna dapat melakukan stock adjustment dan stock opname dengan mencatat
  alasan serta selisih antara stok sistem dan stok fisik.
- Pengguna dapat melihat saldo stok terkini dan riwayat movement setiap produk
  berdasarkan lokasi, tanggal, jenis transaksi, dan nomor referensi.
- Pengguna dapat melihat serta mengekspor laporan saldo stok, kartu stok,
  stok minimum, dan hasil stock opname.

---

## Di Luar Ruang Lingkup

Hal-hal berikut tidak dibangun pada versi 1 meskipun berkaitan dengan inventory:

- Point of Sale, transaksi penjualan, pembayaran, kasir, dan pencetakan struk.
- Akuntansi, jurnal double-entry, buku besar, utang, piutang, dan laporan
  keuangan.
- Proses purchasing lengkap seperti purchase request, purchase order, approval,
  penerimaan invoice, dan pembayaran supplier.
- Sistem multi-cabang dengan sinkronisasi data antardatabase.
- Penggunaan offline dan sinkronisasi otomatis ketika koneksi kembali tersedia.
- Cloud deployment, cloud storage, dan high-availability infrastructure.
- Aplikasi mobile native untuk Android atau iOS.
- Integrasi marketplace, e-commerce, ekspedisi, atau API pihak ketiga.
- Forecasting, rekomendasi pembelian, dan analitik berbasis AI.
- Notifikasi realtime menggunakan Laravel Reverb pada versi awal.
- Pelacakan nomor batch, lot, serial number, atau tanggal kedaluwarsa sebelum
  kebutuhannya diputuskan secara eksplisit.

Penambahan hal di luar ruang lingkup harus dicatat sebagai keputusan baru dalam
`DECISIONS.md`.

---

## Batasan Proyek

- Platform: Aplikasi web responsif untuk desktop dan tablet.
- Backend: Laravel.
- Frontend: Vue.js 3.
- State frontend: Pinia.
- Database: MySQL.
- Komunikasi: REST API dengan format JSON dan prefix `/api/v1`.
- Web server: Lingkungan lokal atau jaringan lokal.
- Penyimpanan file: Laravel local storage.
- Offline: Tidak diperlukan pada versi 1.
- Autentikasi: Email atau username dan password.
- Data: Disimpan di MySQL pada server lokal.
- Bahasa antarmuka: Bahasa Indonesia.
- Zona waktu: Asia/Jakarta.
- Deadline: Belum ditentukan.
- Dependency baru: Harus mendapatkan persetujuan sebelum ditambahkan.

---

## Sasaran Produk

### Akurasi persediaan

Saldo stok harus sesuai dengan akumulasi stock movement yang valid. Sistem harus
mencegah transaksi yang menghasilkan stok negatif, kecuali aturan tersebut
diubah melalui keputusan eksplisit.

### Ketertelusuran

Setiap perubahan stok harus memiliki informasi minimal:

- Produk.
- Lokasi penyimpanan.
- Jenis movement.
- Quantity sebelum dan sesudah transaksi.
- Quantity perubahan.
- Waktu transaksi.
- Nomor referensi.
- Pengguna yang melakukan transaksi.
- Alasan atau catatan jika diperlukan.

### Keamanan transaksi

Transaksi stok harus aman ketika dilakukan oleh beberapa pengguna secara
bersamaan. Perubahan saldo dan pencatatan movement harus berhasil atau gagal
sebagai satu kesatuan.

### Kemudahan operasional

Form transaksi harus dapat digunakan petugas gudang tanpa harus memahami
struktur database atau perhitungan teknis inventory.

---

## Peran Pengguna

### Administrator

Administrator dapat:

- Mengelola pengguna dan hak akses.
- Mengelola seluruh master data.
- Mengakses seluruh lokasi penyimpanan.
- Melakukan seluruh transaksi inventory.
- Melihat seluruh laporan dan audit trail.
- Mengatur konfigurasi inventory yang diizinkan.

### Petugas Gudang

Petugas gudang dapat:

- Melihat produk dan saldo lokasi yang menjadi tanggung jawabnya.
- Mencatat penerimaan dan pengeluaran barang.
- Membuat atau memproses transfer barang.
- Memasukkan hasil perhitungan stock opname.
- Melihat riwayat transaksi yang diizinkan.

### Supervisor Inventory

Supervisor dapat:

- Melihat saldo dan laporan seluruh lokasi yang diizinkan.
- Memeriksa transaksi inventory.
- Melakukan stock adjustment sesuai hak akses.
- Menyelesaikan rekonsiliasi stock opname.
- Memeriksa perbedaan stok fisik dan stok sistem.

Hak akses rinci setiap peran masih harus dikonfirmasi sebelum implementasi.

---

## Persyaratan Fungsional

### Master Produk

Sistem harus memungkinkan pengguna berwenang untuk:

- Membuat dan mengubah produk.
- Menetapkan SKU yang unik.
- Menetapkan barcode jika digunakan.
- Menentukan kategori dan satuan dasar.
- Menentukan batas minimum stok.
- Mengaktifkan atau menonaktifkan produk.
- Mencari produk berdasarkan nama, SKU, atau barcode.

Produk yang sudah memiliki transaksi tidak boleh dihapus permanen.

### Lokasi Penyimpanan

Sistem harus memungkinkan pengguna untuk:

- Membuat dan mengubah lokasi penyimpanan.
- Mengaktifkan atau menonaktifkan lokasi.
- Melihat saldo produk per lokasi.
- Membatasi akses pengguna berdasarkan lokasi jika diperlukan.

Lokasi yang sudah memiliki transaksi tidak boleh dihapus permanen.

### Penerimaan Stok

Pengguna harus dapat mencatat:

- Nomor referensi.
- Tanggal transaksi.
- Supplier jika tersedia.
- Lokasi penerimaan.
- Produk dan quantity.
- Catatan transaksi.

Transaksi yang berhasil harus menambah saldo dan membuat movement `RECEIPT`.

### Pengeluaran Stok

Pengguna harus dapat mencatat:

- Nomor referensi.
- Tanggal transaksi.
- Lokasi pengeluaran.
- Produk dan quantity.
- Tujuan atau alasan pengeluaran.
- Catatan transaksi.

Sistem harus memeriksa stok tersedia sebelum transaksi diposting. Transaksi
yang berhasil harus mengurangi saldo dan membuat movement `ISSUE`.

### Transfer Stok

Pengguna harus dapat:

- Memilih lokasi asal.
- Memilih lokasi tujuan.
- Memilih produk dan quantity.
- Menambahkan nomor referensi dan catatan.
- Memproses transfer sebagai satu transaksi.

Lokasi asal dan tujuan tidak boleh sama. Transfer yang berhasil harus membuat
movement `TRANSFER_OUT` dan `TRANSFER_IN`.

### Stock Adjustment

Pengguna berwenang harus dapat:

- Memilih produk dan lokasi.
- Memasukkan quantity penyesuaian.
- Memilih jenis penambahan atau pengurangan.
- Menuliskan alasan adjustment.
- Mengirim adjustment untuk diposting.

Adjustment harus menghasilkan movement `ADJUSTMENT_IN` atau `ADJUSTMENT_OUT`.

### Stock Opname

Pengguna harus dapat:

- Membuat sesi stock opname berdasarkan lokasi.
- Menentukan produk yang dihitung.
- Memasukkan hasil quantity fisik.
- Melihat selisih terhadap stok sistem.
- Menyelesaikan rekonsiliasi.
- Menghasilkan adjustment berdasarkan selisih yang disetujui.

Saldo stok tidak boleh langsung ditimpa tanpa movement rekonsiliasi.

### Laporan

Pengguna harus dapat melihat:

- Laporan saldo stok per produk dan lokasi.
- Kartu stok berdasarkan periode.
- Riwayat penerimaan dan pengeluaran.
- Daftar produk di bawah batas minimum.
- Laporan transfer stok.
- Laporan adjustment.
- Laporan hasil stock opname.

Filter laporan minimal mencakup periode, produk, kategori, lokasi, dan jenis
movement jika relevan.

---

## Persyaratan Nonfungsional

### Integritas data

- Semua perubahan stok harus menggunakan database transaction.
- Transaksi bersamaan harus menggunakan mekanisme locking yang sesuai.
- Quantity dan nilai persediaan menggunakan tipe decimal, bukan float.
- Stock movement yang sudah diposting tidak boleh dihapus atau diedit langsung.
- Kesalahan transaksi diperbaiki melalui reversal atau adjustment.

### Performa

- Daftar data harus menggunakan server-side pagination.
- Pencarian produk umum ditargetkan memberikan response maksimal 2 detik pada
  lingkungan lokal dengan volume data normal.
- Kolom pencarian, relasi, nomor referensi, dan tanggal transaksi harus memiliki
  index database yang sesuai.
- Frontend tidak boleh mengambil seluruh riwayat movement tanpa pagination.

### Keamanan

- Seluruh endpoint selain login harus memerlukan autentikasi.
- Setiap operasi harus melalui authorization backend.
- Validasi frontend tidak boleh menggantikan validasi backend.
- Secret tidak boleh disimpan di repository atau variable `VITE_*`.
- Aktivitas penting harus dapat dihubungkan dengan pengguna yang melakukannya.

### Kemudahan penggunaan

- Form harus menampilkan pesan validasi yang jelas dalam Bahasa Indonesia.
- Operasi penyimpanan harus memiliki indikator loading.
- Tombol submit harus mencegah pengiriman transaksi berulang.
- Halaman harus memiliki loading state, empty state, error state, dan success
  feedback.
- Transaksi yang berpengaruh terhadap stok harus meminta konfirmasi sebelum
  diposting.

---

## Ketentuan yang Tidak Boleh Diubah

Hal berikut tidak boleh diubah tanpa keputusan eksplisit di `DECISIONS.md`:

- Backend Laravel dan MySQL menjadi sumber kebenaran saldo stok.
- Frontend tidak boleh menentukan hasil akhir transaksi stok.
- Pinia digunakan untuk shared frontend state, bukan business logic inventory.
- Setiap perubahan stok harus menghasilkan stock movement.
- Perubahan saldo dan movement harus disimpan dalam database transaction.
- Stock movement yang sudah diposting tidak boleh dihapus permanen.
- Koreksi transaksi menggunakan reversal atau adjustment baru.
- Stok negatif dinonaktifkan secara default.
- Struktur backend dan frontend menggunakan feature-first.
- Controller tidak boleh mengandung business logic.
- Komponen Vue tidak boleh memanggil API secara langsung.
- Dependency baru tidak boleh ditambahkan tanpa persetujuan.
- Data inventory versi awal disimpan pada MySQL di server lokal.

---

## Kriteria Penerimaan Versi 1

Versi 1 dianggap memenuhi PRD jika:

- Pengguna dapat login dan hanya mengakses fitur sesuai haknya.
- Pengguna dapat mengelola master data inventory.
- Pengguna dapat mencatat penerimaan dan pengeluaran stok.
- Pengguna dapat memindahkan stok antarlokasi.
- Pengguna dapat melakukan adjustment dengan alasan yang tercatat.
- Pengguna dapat menjalankan dan merekonsiliasi stock opname.
- Saldo stok sesuai dengan akumulasi movement yang valid.
- Sistem menolak pengeluaran yang melebihi stok tersedia.
- Seluruh movement dapat ditelusuri ke pengguna dan referensi transaksi.
- Laporan saldo dan kartu stok dapat difilter serta diekspor.
- Pengujian backend berhasil dijalankan dengan `php artisan test`.
- Pemeriksaan format backend berhasil dijalankan dengan `./vendor/bin/pint`.
- Pemeriksaan lint frontend berhasil dijalankan dengan `npm run lint`.

---

## Pertanyaan Terbuka

Pertanyaan berikut harus diselesaikan sebelum fitur terkait mulai dibangun:

- [ ] Apakah satu pengguna dapat mengakses semua lokasi atau hanya lokasi tertentu?
- [ ] Apakah transfer stok membutuhkan status draft, dikirim, dan diterima?
- [ ] Apakah transaksi stok membutuhkan approval sebelum diposting?
- [ ] Apakah produk memiliki satuan konversi, misalnya karton, box, dan pcs?
- [ ] Apakah barcode wajib atau hanya opsional?
- [ ] Apakah sistem perlu menyimpan batch, lot, serial number, atau tanggal
      kedaluwarsa pada versi berikutnya?
- [ ] Apakah metode penilaian persediaan menggunakan FIFO atau weighted average?
- [ ] Apakah stock opname membekukan transaksi lokasi selama proses perhitungan?
- [ ] Format ekspor laporan yang dibutuhkan: Excel, CSV, PDF, atau kombinasi?
- [ ] Apakah autentikasi menggunakan Laravel Sanctum dengan cookie atau token?
- [ ] Berapa perkiraan jumlah produk, lokasi, pengguna, dan transaksi per hari?
- [ ] Apakah data awal perlu diimpor dari file Excel atau sistem sebelumnya?