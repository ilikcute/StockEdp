# Matriks Kriteria Penerimaan PRD (PRD Acceptance Version 1)

Dokumen ini memetakan seluruh kriteria penerimaan dari `PRD.md` terhadap kode implementasi backend/frontend, pengujian otomatis PHPUnit, serta verifikasi manual UAT pada Sistem Inventory Version 1.

---

## 1. Status Keseluruhan Versi 1
Status: **NOT VERIFIED — Stage 10C pending**

---

## 2. Matriks Kepatuhan PRD

| PRD Acceptance Criterion | Code Implementation | Automated Test Coverage | Manual / Rehearsal Verification | Status Compliance |
| --- | --- | --- | --- | :---: |
| **1. Autentikasi & RBAC** | `Sanctum`, `HasRolesAndPermissions`, `RoleAndPermissionSeeder` | `RoleAndPermissionSeederTest`, `AuthenticationTest` | Manual UAT-01 | **PASS — AUTOMATED** |
| **2. Kelola Master Data Inventory** | `ProductController`, `CategoryController`, `UnitController`, `SupplierController`, `LocationController` | Feature CRUD tests per master data module | Manual UAT-02 | **PASS — AUTOMATED** |
| **3. Penerimaan & Pengeluaran Stok** | `StockReceiptController`, `StockIssueController`, `PostStockReceiptAction`, `PostStockIssueAction` | `StockReceiptTest`, `StockIssueTest` | Manual UAT-03, UAT-04 | **PASS — AUTOMATED** |
| **4. Transfer Stok Antarlokasi** | `StockTransferController`, `SendStockTransferAction`, `ReceiveStockTransferAction` | `StockTransferTest` | Manual UAT-05 | **PASS — AUTOMATED** |
| **5. Adjustment dengan Alasan** | `StockAdjustmentController`, `PostStockAdjustmentAction`, `AdjustmentReason` | `StockAdjustmentTest` | Manual UAT-06 | **PASS — AUTOMATED** |
| **6. Stock Opname & Rekonsiliasi** | `StockOpnameController`, `StartStockOpnameAction`, `PostStockOpnameAction` | `StockOpnameTest` | Manual UAT-07 | **PASS — AUTOMATED** |
| **7. Saldo Sesuai Akumulasi Movement** | `InventoryBalance`, `StockMovement`, `DecimalQuantity` | `BalanceMovementReconciliationTest`, `ReleaseDatasetIntegrityTest` | Manual UAT-08, UAT-10 | **PASS — AUTOMATED** |
| **8. Penolakan Insufficient Stock** | `PostStockIssueAction`, `InsufficientStockException` | `TransactionRollbackTest` | Manual UAT-04 | **PASS — AUTOMATED** |
| **9. Traceability Movement & User** | `StockMovement` schema (`user_id`, `reference_type`, `reference_id`, `quantity_before`, `quantity_after`) | `StockMovementTest`, `ReleaseDatasetIntegrityTest` | Manual UAT-10 | **PASS — AUTOMATED** |
| **10. Filter Laporan & Ekspor CSV** | `ReportExportController`, `ReportCsvExportService` | `ReportCsvExportTest`, `RateLimitingTest` | Manual UAT-08, UAT-09 | **PASS — AUTOMATED** |
| **11. Manual User Acceptance Test (UAT)** | Frontend Vue pages & user workflows | Unit / Feature Tests | Matriks UAT-01 s.d UAT-12 | **NOT VERIFIED** |
| **12. Responsive UI ($1280 \times 800$, $1024 \times 768$)** | CSS Layout & Viewport rules | Vite / ESLint | Manual UI Verification | **NOT VERIFIED** |
| **13. Operasi Umum Selesai < 60 Detik** | Application workflow design | Automated feature tests | Operational Drill | **NOT VERIFIED** |
| **14. Response Time API <= 2000 ms** | Optimized Indexing & Cursor Streaming | Benchmark script | Performance Measurement Drill | **NOT VERIFIED** |
| **15. Backup & Restore Drill** | `mysqldump` & SQL import script | N/A | Database Rehearsal Drill | **NOT VERIFIED** |
| **16. Production-like Rehearsal** | Seeder & Environment Guard | `ReleaseDatasetIntegrityTest` | Rehearsal Execution Drill | **NOT VERIFIED** |
| **17. Format Code Backend (`Pint`)** | `./vendor/bin/pint --test` | Laravel Pint Check | N/A | **PASS — AUTOMATED** |
| **18. Format Code Frontend (`ESLint`)** | `npm run lint` | ESLint Zero-Warning Check | N/A | **PASS — AUTOMATED** |
| **19. Inventory Valuation / Accounting** | Scope Deferred to Version 2 | N/A | N/A | **NOT APPLICABLE** |
