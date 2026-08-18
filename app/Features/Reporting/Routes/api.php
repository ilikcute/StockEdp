<?php

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
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('reports/filter-options/base', [ReportFilterOptionsController::class, 'baseOptions']);
    Route::get('reports/filter-options/products', [ReportFilterOptionsController::class, 'productOptions']);
    Route::get('reports/filter-options/suppliers', [ReportFilterOptionsController::class, 'supplierOptions']);

    Route::get('reports/inventory-balances', [InventoryBalanceReportController::class, 'index']);
    Route::get('reports/inventory-balances/export', [ReportExportController::class, 'inventoryBalances'])->name('reports.inventory-balances.export');

    Route::get('reports/low-stock', [LowStockReportController::class, 'index']);
    Route::get('reports/low-stock/export', [ReportExportController::class, 'lowStock'])->name('reports.low-stock.export');

    Route::get('reports/inventory-movement', [InventoryMovementReportController::class, 'index'])->name('reports.inventory-movement.index');
    Route::get('reports/inventory-movement/export', [ReportExportController::class, 'inventoryMovement'])->name('reports.inventory-movement.export');

    Route::get('reports/stock-card', [StockCardReportController::class, 'index']);
    Route::get('reports/stock-card/export', [ReportExportController::class, 'stockCard'])->name('reports.stock-card.export');

    Route::get('reports/stock-receipts', StockReceiptReportController::class);
    Route::get('reports/stock-receipts/export', [ReportExportController::class, 'stockReceipts'])->name('reports.stock-receipts.export');

    Route::get('reports/stock-issues', StockIssueReportController::class);
    Route::get('reports/stock-issues/export', [ReportExportController::class, 'stockIssues'])->name('reports.stock-issues.export');

    Route::get('reports/stock-transfers', StockTransferReportController::class);
    Route::get('reports/stock-transfers/export', [ReportExportController::class, 'stockTransfers'])->name('reports.stock-transfers.export');

    Route::get('reports/stock-adjustments', StockAdjustmentReportController::class);
    Route::get('reports/stock-adjustments/export', [ReportExportController::class, 'stockAdjustments'])->name('reports.stock-adjustments.export');

    Route::get('reports/stock-opnames', StockOpnameReportController::class);
    Route::get('reports/stock-opnames/export', [ReportExportController::class, 'stockOpnames'])->name('reports.stock-opnames.export');
});
