<?php

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Inventory\Controllers\InventoryBalanceController;
use App\Features\Inventory\Controllers\StockAdjustmentController;
use App\Features\Inventory\Controllers\StockIssueController;
use App\Features\Inventory\Controllers\StockMovementController;
use App\Features\Inventory\Controllers\StockOpnameController;
use App\Features\Inventory\Controllers\StockReceiptController;
use App\Features\Inventory\Controllers\StockTransferController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('inventory/balances', [InventoryBalanceController::class, 'index'])
        ->middleware('permission:'.PermissionCode::INVENTORY_BALANCES_VIEW->value);

    Route::get('inventory/movements', [StockMovementController::class, 'index'])
        ->middleware('permission:'.PermissionCode::INVENTORY_MOVEMENTS_VIEW->value);

    Route::get('inventory/movements/{stockMovement}', [StockMovementController::class, 'show'])
        ->middleware('permission:'.PermissionCode::INVENTORY_MOVEMENTS_VIEW->value);

    // Stock Receipts (Fase 4B)
    Route::get('/stock-receipts', [StockReceiptController::class, 'index']);
    Route::post('/stock-receipts', [StockReceiptController::class, 'store']);
    Route::get('/stock-receipts/{stockReceipt}', [StockReceiptController::class, 'show']);
    Route::patch('/stock-receipts/{stockReceipt}', [StockReceiptController::class, 'update']);
    Route::post('/stock-receipts/{stockReceipt}/post', [StockReceiptController::class, 'post']);
    Route::post('/stock-receipts/{stockReceipt}/cancel', [StockReceiptController::class, 'cancel']);

    // Stock Issues (Fase 4C)
    Route::get('/stock-issues', [StockIssueController::class, 'index']);
    Route::post('/stock-issues', [StockIssueController::class, 'store']);
    Route::get('/stock-issues/{stockIssue}', [StockIssueController::class, 'show']);
    Route::patch('/stock-issues/{stockIssue}', [StockIssueController::class, 'update']);
    Route::post('/stock-issues/{stockIssue}/post', [StockIssueController::class, 'post']);
    Route::post('/stock-issues/{stockIssue}/cancel', [StockIssueController::class, 'cancel']);

    // Stock Transfers (Fase 5)
    Route::get('/stock-transfers', [StockTransferController::class, 'index']);
    Route::post('/stock-transfers', [StockTransferController::class, 'store']);
    Route::get('/stock-transfers/{stockTransfer}', [StockTransferController::class, 'show']);
    Route::patch('/stock-transfers/{stockTransfer}', [StockTransferController::class, 'update']);
    Route::post('/stock-transfers/{stockTransfer}/send', [StockTransferController::class, 'send']);
    Route::post('/stock-transfers/{stockTransfer}/receive', [StockTransferController::class, 'receive']);
    Route::post('/stock-transfers/{stockTransfer}/cancel', [StockTransferController::class, 'cancel']);

    // Stock Adjustments (Fase 6A)
    Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index']);
    Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store']);
    Route::get('/stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'show']);
    Route::patch('/stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'update']);
    Route::post('/stock-adjustments/{stockAdjustment}/post', [StockAdjustmentController::class, 'post']);
    Route::post('/stock-adjustments/{stockAdjustment}/cancel', [StockAdjustmentController::class, 'cancel']);

    // Stock Opnames (Fase 7B)
    Route::get('/stock-opnames', [StockOpnameController::class, 'index']);
    Route::post('/stock-opnames', [StockOpnameController::class, 'store']);
    Route::get('/stock-opnames/{stockOpname}', [StockOpnameController::class, 'show']);
    Route::patch('/stock-opnames/{stockOpname}', [StockOpnameController::class, 'update']);
    Route::post('/stock-opnames/{stockOpname}/start', [StockOpnameController::class, 'start']);
    Route::patch('/stock-opnames/{stockOpname}/items/{itemId}/count', [StockOpnameController::class, 'count']);
    Route::post('/stock-opnames/{stockOpname}/items', [StockOpnameController::class, 'addUnexpected']);
    Route::post('/stock-opnames/{stockOpname}/complete', [StockOpnameController::class, 'complete']);
    Route::post('/stock-opnames/{stockOpname}/reopen', [StockOpnameController::class, 'reopen']);
    Route::post('/stock-opnames/{stockOpname}/post', [StockOpnameController::class, 'post']);
    Route::post('/stock-opnames/{stockOpname}/cancel', [StockOpnameController::class, 'cancel']);
});
