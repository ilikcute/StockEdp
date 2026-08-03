<?php

use App\Features\Reporting\Controllers\InventoryBalanceReportController;
use App\Features\Reporting\Controllers\LowStockReportController;
use App\Features\Reporting\Controllers\StockCardReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('reports/inventory-balances', [InventoryBalanceReportController::class, 'index']);
    Route::get('reports/low-stock', [LowStockReportController::class, 'index']);
    Route::get('reports/stock-card', [StockCardReportController::class, 'index']);
});
