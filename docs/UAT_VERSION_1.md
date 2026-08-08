# Matriks User Acceptance Testing (UAT Version 1) — EXECUTED

Dokumen ini berisi matriks dan bukti eksekusi Pengujian Penerimaan Pengguna (User Acceptance Testing / UAT) untuk Sistem Inventory Version 1. Status dokumen saat ini adalah **EXECUTED — 12 / 12 CANONICAL SCENARIOS PASS (STAGE 10C-2 CLOSED)**.

> **Catatan Eksekusi UAT Operasional (dieksekusi 08-Agu-2026):**
> Seluruh 12 skenario kanonikal UAT dieksekusi terhadap database rehearsal terisolasi (`stockedp_release_uat`) yang dipopulasi oleh `ReleaseVerificationSeeder` (>10.000 record gerakan stok). Seluruh skenario dinyatakan **PASS 12/12** tanpa kegagalan data, tanpa kebocoran exception, dan dengan tingkat kontinuitas buku besar (ledger chain continuity) 100%.

---

## 1. Prekondisi Akun Pengguna UAT

- **Akun Administrator UAT (`uat_admin`)**: Memiliki 64 granular permissions penuh untuk pengujian master data & otorisasi.
- **Akun Petugas Gudang / Warehouse Officer (`rel_user_04`)**: Hak akses terbatas pada penerimaan, pengeluaran, dan transfer stok. Dibatasi dari posting adjustment dan opname.
- **Akun Supervisor A (`rel_user_01`), Supervisor B (`rel_user_02`), Supervisor C (`rel_user_03`)**: Memiliki izin posting (`STOCK_ADJUSTMENTS_POST` dan `STOCK_OPNAMES_POST`). Digunakan untuk mengisolasi pengujian aturan **Maker-Checker** (pembuat dokumen ditolak saat mencoba memposting dokumennya sendiri).
- **Akun Petugas Lokasi Terisolasi (`uat_loc01_officer` & `uat_loc02_officer`)**: Hak akses lokasi terbatas pada `REL-LOC-01` dan `REL-LOC-02` untuk menguji pencegahan IDOR (Insecure Direct Object Reference).

---

## 2. Matriks Skenario & Bukti Eksekusi UAT Kanonikal

| ID | Role Test | Precondition | Langkah Pengujian | Hasil yang Diharapkan | Status Verification | Tanggal & Bukti Eksekusi |
| --- | --- | --- | --- | --- | :---: | --- |
| **UAT-01** | All Roles | Seeder Role/Permission aktif | Login sebagai Admin, Petugas, dan Supervisor; Uji endpoint tanpa auth & kredensial salah | Admin memiliki 64 permission; Request tanpa auth mengembalikan HTTP 401; Kredensial tidak valid ditolak HTTP 401/422 | **PASS** | **08-Agu-2026** — Admin 64 perms verified; HTTP 401 unauth & 422/401 invalid creds confirmed. |
| **UAT-02** | Admin | Admin Authenticated | Menambah Produk, Kategori, Satuan, Supplier, Lokasi; Uji duplikat SKU & Barcode | Master data tersimpan presisi; Unique constraint SKU & Barcode aktif; Desimal `minimum_stock` `10.0000` tersimpan utuh | **PASS** | **08-Agu-2026** — SKU `UAT-SKU-*` & Barcode unik enforced; minimum stock `10.0000` precision verified. |
| **UAT-03** | Petugas Gudang | Master data & Lokasi tersedia | Membuat Draft & Posting Penerimaan Stok (Stock Receipt) | Status dokumen `POSTED`, saldo `inventory_balances` bertambah dari `0` ke `100.5000` secara atomik | **PASS** | **08-Agu-2026** — Receipt `UAT-RCP-*` posted; balance `REL-LOC-01` bertambah dari `0` ke `100.5000`. |
| **UAT-04** | Petugas Gudang | Saldo stok tersedia | Membuat Pengeluaran Stok (Stock Issue) normal & pengeluaran melebihi saldo (overdraft) | Issue normal mengurangi saldo ke `75.2500`; Overdraft ditolak `InsufficientStockException` (HTTP 422) tanpa partial commit | **PASS** | **08-Agu-2026** — Issue normal reduce balance ke `75.2500`; overdraft rejected cleanly with 0 side effect. |
| **UAT-05** | Petugas Gudang | Saldo di Gudang Utama | Transfer Stok `20.0000` dari `REL-LOC-01` ke `REL-LOC-02` (Send -> Receive) | Status `SENT` memicu `TRANSFER_OUT` (saldo loc1 = `55.2500`); Status `RECEIVED` memicu `TRANSFER_IN` (saldo loc2 = `20.0000`) | **PASS** | **08-Agu-2026** — Transfer `UAT-TRF-*` cycled DRAFT->SENT->RECEIVED; balances loc1 `55.2500`, loc2 `20.0000`. |
| **UAT-06** | Supervisor A & Supervisor B | Lokasi & saldo terisi | Supervisor A buat Draft Adjustment -> Supervisor A coba POST (ditolak) -> Supervisor B POST | Maker-checker terisolasi: Supervisor A self-post ditolak 403 (draft utuh, 0 balance delta); Supervisor B berhasil post (+5 -> `60.2500`) | **PASS** | **08-Agu-2026** — Self-post Supervisor A rejected 403 (`MAKER_CHECKER_VIOLATION`); Supervisor B posted -> balance `60.2500`. |
| **UAT-07** | Supervisor A/B/C & Officer | Lokasi aktif | Siklus Opname (Start -> Blind Count -> Complete -> Post) dengan pemisahan Creator, Counter, Poster | Lock `409 LOCATION_FROZEN` aktif; Payload API counter menyembunyikan snapshot; Creator (403) & Counter (403) ditolak POST; Poster (200) memposting; Surplus (+10 -> `70.2500`) & Shortage (-5 -> `15.0000`) verified; Lokasi unfreezed | **PASS** | **08-Agu-2026** — Isolated maker-checker (Creator 403, Counter 403, Poster 200); Blind count API hidden; Surplus `OPNAME_IN` & Shortage `OPNAME_OUT` verified. |
| **UAT-08** | All Roles | Transaksi terisi | Membuka Laporan Saldo, Stok Minimum, Kartu Stok, dan 5 Laporan Transaksi | Seluruh 8 laporan (Balances, LowStock, Receipts, Issues, Transfers, Adjustments, Opnames, StockCard) terquery cepat dengan filter & location scope | **PASS** | **08-Agu-2026** — 8/8 report services getReport executed with active filters & location scoping. |
| **UAT-09** | Supervisor | Permission export | Mengunduh CSV untuk seluruh 8 jenis laporan | 8/8 file CSV ber-BOM UTF-8 terunduh dengan header Bahasa Indonesia, formula injection escaped (`'=SUM...`), desimal negatif (`-0.0001`) presisi | **PASS** | **08-Agu-2026** — 8/8 exports return HTTP 200, UTF-8 BOM, attachment disposition, formula escaped & negative decimals intact. |
| **UAT-10** | Admin & Supervisor | Record movement ada | Menelusuri record pergerakan stok (Stock Card) pada SKU teruji | Terlacaknya produk, lokasi, type, `quantity_before`, `quantity_after`, reference_type, user_id, timestamp; Kontinuitas rantai ledger **100%** | **PASS** | **08-Agu-2026** — 5 movements traced for SKU `UAT-SKU-*`; previous `quantity_after` == next `quantity_before` 100% verified. |
| **UAT-11** | All Roles | API rate limiter | Mengirim > 60 request/menit ke API terproteksi throttle | Respons HTTP 429 dengan header `Retry-After`; Envelope JSON kanonikal tanpa SQL details; User tidak ter-logout dan UI form aman | **PASS** | **08-Agu-2026** — Rate limit >60 req/min triggers HTTP 429 with `Retry-After` header & canonical JSON envelope. |
| **UAT-12** | All Roles | Variasi resolusi layar | Akses 11 halaman utama aplikasi pada viewport desktop ($1280 \times 800$) dan tablet ($1024 \times 768$) | Tampilan responsive, modal dialog pas tanpa clipped, tabel & navigasi dapat digunakan tanpa horizontal overflow terlarang | **PASS** | **08-Agu-2026** — 11 core pages verified at 1280x800 & 1024x768 viewports: clean layout, accessible actions. |

---

## 3. Bukti Pengujian Tambahan & Integritas Sistem

### 3.1 Double-Submission Idempotency Guard
- **Receipt POST, Issue POST, Transfer SEND, Adjustment POST, Opname POST**: Percobaan pengiriman/posting ulang pada dokumen yang sudah berstatus final ditolak secara aman dengan HTTP `409 Conflict` / `422 Unprocessable Entity` tanpa menghasilkan duplikasi pergerakan stok atau perubahan saldo.

### 3.2 Kontrak Response Errors HTTP
- Seluruh response error HTTP (`401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `429 Too Many Requests`) terkonfirmasi mengembalikan JSON envelope kanonikal (`message`, `code`, `errors`) **tanpa membocorkan detail SQL, stack trace, atau path direktori server**.

### 3.3 Rekonsiliasi Akhir Buku Besar (Ledger Reconciliation)
- **Produk Test**: `UAT-SKU-1786171635`
- **Lokasi REL-LOC-01**: Saldo Akhir (`70.2500`) == Akumulasi Delta Gerakan Ledger (`70.2500`). `bccomp` Delta == 0 (**100% MATCH**).
- **Lokasi REL-LOC-02**: Saldo Akhir (`15.0000`) == Akumulasi Delta Gerakan Ledger (`15.0000`). `bccomp` Delta == 0 (**100% MATCH**).
