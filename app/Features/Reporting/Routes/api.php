<?php

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Reporting\Controllers\FieldBalanceReportController;
use App\Features\Reporting\Controllers\InventoryBalanceReportController;
use App\Features\Reporting\Controllers\InventoryMovementReportController;
use App\Features\Reporting\Controllers\LowStockReportController;
use App\Features\Reporting\Controllers\ReportExportController;
use App\Features\Reporting\Controllers\ReportFilterOptionsController;
use App\Features\Reporting\Controllers\StockAdjustmentReportController;
use App\Features\Reporting\Controllers\StockCardReportController;
use App\Features\Reporting\Controllers\StockIssueReportController;
use App\Features\Reporting\Controllers\StockOpnameReportController;
use App\Features\Reporting\Controllers\StockReceiptReportController;
use App\Features\Reporting\Controllers\StockTransferReportController;
use App\Features\Reporting\Controllers\StoreAllocationReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('reports/filter-options/base', [ReportFilterOptionsController::class, 'baseOptions'])
        ->middleware('permission:reports.view|reports.inventory_balance.view|reports.low_stock.view|reports.stock_card.view|reports.stock_receipts.view|reports.stock_issues.view|reports.stock_transfers.view|reports.stock_adjustments.view|reports.stock_opnames.view|reports.inventory_movement.view|reports.store_allocations.view|reports.field_balances.view');

    Route::get('reports/filter-options/products', [ReportFilterOptionsController::class, 'productOptions'])
        ->middleware('permission:reports.view|reports.inventory_balance.view|reports.low_stock.view|reports.stock_card.view|reports.stock_receipts.view|reports.stock_issues.view|reports.stock_transfers.view|reports.stock_adjustments.view|reports.stock_opnames.view|products.view');

    Route::get('reports/filter-options/suppliers', [ReportFilterOptionsController::class, 'supplierOptions'])
        ->middleware('permission:reports.view|reports.stock_receipts.view|suppliers.view');

    Route::get('reports/inventory-balances', [InventoryBalanceReportController::class, 'index'])
        ->middleware('permission:' . PermissionCode::REPORTS_INVENTORY_BALANCE_VIEW->value);
    Route::get('reports/inventory-balances/export', [ReportExportController::class, 'inventoryBalances'])
        ->name('reports.inventory-balances.export')
        ->middleware('permission:' . PermissionCode::REPORTS_INVENTORY_BALANCE_VIEW->value . '|' . PermissionCode::REPORTS_EXPORT->value);

    Route::get('reports/field-balances', FieldBalanceReportController::class)
        ->middleware('permission:' . PermissionCode::REPORTS_FIELD_BALANCES_VIEW->value);
    Route::get('reports/field-balances/export', [ReportExportController::class, 'fieldBalances'])
        ->name('reports.field-balances.export')
        ->middleware('permission:' . PermissionCode::REPORTS_FIELD_BALANCES_VIEW->value . '|' . PermissionCode::REPORTS_EXPORT->value);

    Route::get('reports/low-stock', [LowStockReportController::class, 'index'])
        ->middleware('permission:' . PermissionCode::REPORTS_LOW_STOCK_VIEW->value);
    Route::get('reports/low-stock/export', [ReportExportController::class, 'lowStock'])
        ->name('reports.low-stock.export')
        ->middleware('permission:' . PermissionCode::REPORTS_LOW_STOCK_VIEW->value . '|' . PermissionCode::REPORTS_EXPORT->value);

    Route::get('reports/inventory-movement', [InventoryMovementReportController::class, 'index'])
        ->name('reports.inventory-movement.index')
        ->middleware('permission:' . PermissionCode::REPORTS_INVENTORY_MOVEMENT_VIEW->value . '|' . PermissionCode::REPORTS_VIEW->value . '|' . PermissionCode::DASHBOARD_VIEW->value);
    Route::get('reports/inventory-movement/export', [ReportExportController::class, 'inventoryMovement'])
        ->name('reports.inventory-movement.export')
        ->middleware('permission:' . PermissionCode::REPORTS_INVENTORY_MOVEMENT_VIEW->value . '|' . PermissionCode::REPORTS_EXPORT->value);

    Route::get('reports/stock-card', [StockCardReportController::class, 'index'])
        ->middleware('permission:' . PermissionCode::REPORTS_STOCK_CARD_VIEW->value);
    Route::get('reports/stock-card/export', [ReportExportController::class, 'stockCard'])
        ->name('reports.stock-card.export')
        ->middleware('permission:' . PermissionCode::REPORTS_STOCK_CARD_VIEW->value . '|' . PermissionCode::REPORTS_EXPORT->value);

    Route::get('reports/store-allocations', StoreAllocationReportController::class)
        ->middleware('permission:' . PermissionCode::REPORTS_STORE_ALLOCATIONS_VIEW->value);
    Route::get('reports/store-allocations/export', [ReportExportController::class, 'storeAllocations'])
        ->name('reports.store-allocations.export')
        ->middleware('permission:' . PermissionCode::REPORTS_STORE_ALLOCATIONS_VIEW->value . '|' . PermissionCode::REPORTS_EXPORT->value);

    Route::get('reports/stock-receipts', StockReceiptReportController::class)
        ->middleware('permission:' . PermissionCode::REPORTS_STOCK_RECEIPTS_VIEW->value);
    Route::get('reports/stock-receipts/export', [ReportExportController::class, 'stockReceipts'])
        ->name('reports.stock-receipts.export')
        ->middleware('permission:' . PermissionCode::REPORTS_STOCK_RECEIPTS_VIEW->value . '|' . PermissionCode::REPORTS_EXPORT->value);

    Route::get('reports/stock-issues', StockIssueReportController::class)
        ->middleware('permission:' . PermissionCode::REPORTS_STOCK_ISSUES_VIEW->value);
    Route::get('reports/stock-issues/export', [ReportExportController::class, 'stockIssues'])
        ->name('reports.stock-issues.export')
        ->middleware('permission:' . PermissionCode::REPORTS_STOCK_ISSUES_VIEW->value . '|' . PermissionCode::REPORTS_EXPORT->value);

    Route::get('reports/stock-transfers', StockTransferReportController::class)
        ->middleware('permission:' . PermissionCode::REPORTS_STOCK_TRANSFERS_VIEW->value);
    Route::get('reports/stock-transfers/export', [ReportExportController::class, 'stockTransfers'])
        ->name('reports.stock-transfers.export')
        ->middleware('permission:' . PermissionCode::REPORTS_STOCK_TRANSFERS_VIEW->value . '|' . PermissionCode::REPORTS_EXPORT->value);

    Route::get('reports/stock-adjustments', StockAdjustmentReportController::class)
        ->middleware('permission:' . PermissionCode::REPORTS_STOCK_ADJUSTMENTS_VIEW->value);
    Route::get('reports/stock-adjustments/export', [ReportExportController::class, 'stockAdjustments'])
        ->name('reports.stock-adjustments.export')
        ->middleware('permission:' . PermissionCode::REPORTS_STOCK_ADJUSTMENTS_VIEW->value . '|' . PermissionCode::REPORTS_EXPORT->value);

    Route::get('reports/stock-opnames', StockOpnameReportController::class)
        ->middleware('permission:' . PermissionCode::REPORTS_STOCK_OPNAMES_VIEW->value);
    Route::get('reports/stock-opnames/export', [ReportExportController::class, 'stockOpnames'])
        ->name('reports.stock-opnames.export')
        ->middleware('permission:' . PermissionCode::REPORTS_STOCK_OPNAMES_VIEW->value . '|' . PermissionCode::REPORTS_EXPORT->value);
});
