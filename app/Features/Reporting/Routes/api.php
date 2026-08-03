<?php

use App\Features\Reporting\Controllers\InventoryBalanceReportController;
use App\Features\Reporting\Controllers\LowStockReportController;
use App\Features\Reporting\Controllers\StockAdjustmentReportController;
use App\Features\Reporting\Controllers\StockCardReportController;
use App\Features\Reporting\Controllers\StockIssueReportController;
use App\Features\Reporting\Controllers\StockOpnameReportController;
use App\Features\Reporting\Controllers\StockReceiptReportController;
use App\Features\Reporting\Controllers\StockTransferReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('reports/inventory-balances', [InventoryBalanceReportController::class, 'index']);
    Route::get('reports/low-stock', [LowStockReportController::class, 'index']);
    Route::get('reports/stock-card', [StockCardReportController::class, 'index']);
    Route::get('reports/stock-receipts', StockReceiptReportController::class);
    Route::get('reports/stock-issues', StockIssueReportController::class);
    Route::get('reports/stock-transfers', StockTransferReportController::class);
    Route::get('reports/stock-adjustments', StockAdjustmentReportController::class);
    Route::get('reports/stock-opnames', StockOpnameReportController::class);
});
