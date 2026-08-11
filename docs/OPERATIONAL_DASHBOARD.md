# OPERATIONAL INVENTORY DASHBOARD & COMPUTED ALERT CENTER

Dokumentasi arsitektur, kontrak API, dan pedoman penggunaan untuk **Operational Inventory Dashboard & Computed Alert Center (Fase 12A)** pada sistem `StockEdp`.

---

## 1. Arsitektur & Prinsip Utuh

1. **Read-Only Invariant**: Dashboard operasional **TIDAK BOLEH** mengubah state data persediaan, saldo (`inventory_balances`), pergerakan stok (`stock_movements`), atau status dokumen transaksi (`delta = 0`).
2. **On-Demand Computed Alerts**: Tidak ada tabel mutasi/persisten seperti `notifications` atau `alerts`. Peringatan dihitung secara langsung (*on-the-fly*) saat request endpoint `/api/v1/dashboard` diterima.
3. **Canonical Parity**:
   - Jumlah `low_stock_count` pada dashboard **wajib 100% identik** dengan total hasil query Laporan Stok Minimum (`/api/v1/reports/low-stock`) untuk lokasi dan kriteria yang sama.
   - Saldo dan pergerakan persediaan menggunakan representasi string decimal 4 presisi (`0.0000`) tanpa pemotongan casting float/number.
4. **RBAC & Location Scoping**:
   - Permission: `dashboard.view`.
   - Lokasi wajib dibatasi sesuai dengan `$user->getAllowedLocationIds()`.
   - Akses lokasi di luar izin pengguna akan menghasilkan response `403 Forbidden`.

---

## 2. Kontrak Endpoint API

### `GET /api/v1/dashboard`

**Headers**:
- `Authorization: Bearer <token>`
- `Accept: application/json`

**Query Parameters**:
- `location_id` (optional, integer): Filter berdasarkan ID lokasi persediaan spesifik yang diizinkan.
- `period` (optional, string): Preset periode waktu (`today`, `7d`, `30d`). Default: `7d`.

**Struktur Response JSON**:

```json
{
  "success": true,
  "message": "Dashboard operational data loaded successfully.",
  "data": {
    "filters": {
      "period": "7d",
      "date_from": "2026-08-05",
      "date_to": "2026-08-11",
      "location_id": null
    },
    "inventory_health": {
      "low_stock_count": 2,
      "out_of_stock_count": 1,
      "active_opname_count": 1,
      "frozen_location_count": 0
    },
    "operational_queue": {
      "receipt_draft_count": 1,
      "issue_draft_count": 0,
      "transfer_awaiting_receipt_count": 1,
      "adjustment_pending_count": 0,
      "opname_in_progress_count": 1,
      "opname_awaiting_post_count": 0
    },
    "period_activity": {
      "posted_receipt_count": 5,
      "posted_issue_count": 3,
      "received_transfer_count": 2,
      "movement_count": 12
    },
    "alerts": [
      {
        "type": "OUT_OF_STOCK",
        "severity": "CRITICAL",
        "title": "Stok Habis Membutuhkan Penanganan",
        "message": "Terdapat 1 item persediaan dengan stok 0.",
        "count": 1,
        "route_name": "reports.inventory-balances",
        "permission": "reports.inventory_balance.view"
      },
      {
        "type": "LOW_STOCK",
        "severity": "WARNING",
        "title": "Stok Minimum Perlu Perhatian",
        "message": "Terdapat 2 produk di bawah batas stok minimum.",
        "count": 2,
        "route_name": "reports.low-stock",
        "permission": "reports.low_stock.view"
      }
    ],
    "recent_activity": [
      {
        "id": 105,
        "occurred_at": "2026-08-11T10:00:00+07:00",
        "type": "RECEIPT",
        "reference_number": "RC-20260811-001",
        "product_sku": "PRD-001",
        "product_name": "Beras Premium 5kg",
        "unit_symbol": "sak",
        "location_code": "GUD-UTAMA",
        "location_name": "Gudang Utama",
        "quantity": "50.0000",
        "performed_by": "Budi Santoso"
      }
    ],
    "top_issued_products": [
      {
        "product_id": 10,
        "sku": "PRD-010",
        "name": "Minyak Goreng 2L",
        "unit_symbol": "pouch",
        "total_quantity": "120.0000",
        "movement_count": 8
      }
    ],
    "top_received_products": [
      {
        "product_id": 1,
        "sku": "PRD-001",
        "name": "Beras Premium 5kg",
        "unit_symbol": "sak",
        "total_quantity": "500.0000",
        "movement_count": 5
      }
    ],
    "generated_at": "2026-08-11T17:30:00+07:00"
  }
}
```

---

## 3. Matriks Peringatan Komputatif (Computed Alert Matrix)

| Kode Alert | Severity | Deskripsi & Kriteria Kuis | Target Modul Navigation |
|---|---|---|---|
| `OUT_OF_STOCK` | `CRITICAL` | Saldo persediaan = `0.0000` di lokasi terjangkau | Saldo Stok (`/inventory-balances`) |
| `LOW_STOCK` | `WARNING` | Saldo persediaan < Minimum Stok Produk | Laporan Stok Minimum (`/reports/low-stock`) |
| `TRANSFER_AWAITING_RECEIPT` | `INFO` | Dokumen Transfer Stok ber-status `SENT` (Dikirim & Transit) | Transfer Stok (`/stock-transfers`) |
| `ADJUSTMENT_PENDING` | `INFO` | Draft Penyesuaian Stok (`DRAFT`) belum diposting | Stock Adjustment (`/stock-adjustments`) |
| `OPNAME_IN_PROGRESS` | `INFO` | Stock Opname status `IN_PROGRESS` sedang berlangsung | Stock Opname (`/stock-opnames`) |
| `OPNAME_AWAITING_POST` | `INFO` | Stock Opname status `COUNTED` selesai dihitung & menunggu posting | Stock Opname (`/stock-opnames`) |
| `FROZEN_LOCATION` | `INFO` | Lokasi persediaan dalam kondisi beku (`is_frozen = true`) | Lokasi (`/locations`) |

---

## 4. Pengujian & Verifikasi

Suite pengujian backend lengkap tersedia pada:
`tests/Feature/Dashboard/`

1. `DashboardAuthorizationTest.php`: Verifikasi guest, forbidden, admin, warehouse officer, dan inventory supervisor.
2. `DashboardLocationScopeTest.php`: Verifikasi isolasi lokasi data persediaan dan penolakan 403 untuk lokasi di luar wewenang.
3. `DashboardLowStockParityTest.php`: Verifikasi 100% parity count stok minimum terhadap Laporan Stok Minimum.
4. `DashboardOperationalQueueTest.php`: Verifikasi akurasi perhitungan antrean draft dan transit.
5. `DashboardReadOnlyIntegrityTest.php`: Verifikasi zero-delta mutasi persediaan saat request dashboard.
6. `DashboardRecentActivityTest.php`: Verifikasi limit 10 pergerakan terkini dan pengurutan terbaru.
7. `DashboardTopMovementsTest.php`: Verifikasi rangkuman dan agregasi presisi decimal kuantitas keluar/masuk.
8. `DashboardPerformanceTest.php`: Verifikasi ambang batas waktu respon backend (< 2000 ms).
