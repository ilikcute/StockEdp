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
    Route::get('/stock-receipts', [StockReceiptController::class, 'index'])
        ->middleware('permission:'.PermissionCode::STOCK_RECEIPTS_VIEW->value);
    Route::post('/stock-receipts', [StockReceiptController::class, 'store'])
        ->middleware('permission:'.PermissionCode::STOCK_RECEIPTS_CREATE->value);
    Route::get('/stock-receipts/{stockReceipt}', [StockReceiptController::class, 'show'])
        ->middleware('permission:'.PermissionCode::STOCK_RECEIPTS_VIEW->value);
    Route::patch('/stock-receipts/{stockReceipt}', [StockReceiptController::class, 'update'])
        ->middleware('permission:'.PermissionCode::STOCK_RECEIPTS_UPDATE->value);
    Route::post('/stock-receipts/{stockReceipt}/post', [StockReceiptController::class, 'post'])
        ->middleware('permission:'.PermissionCode::STOCK_RECEIPTS_POST->value);
    Route::post('/stock-receipts/{stockReceipt}/cancel', [StockReceiptController::class, 'cancel'])
        ->middleware('permission:'.PermissionCode::STOCK_RECEIPTS_CANCEL->value);
    Route::delete('/stock-receipts/{stockReceipt}', [StockReceiptController::class, 'destroy']);

    // Stock Issues (Fase 4C)
    Route::get('/stock-issues', [StockIssueController::class, 'index'])
        ->middleware('permission:'.PermissionCode::STOCK_ISSUES_VIEW->value);
    Route::post('/stock-issues', [StockIssueController::class, 'store'])
        ->middleware('permission:'.PermissionCode::STOCK_ISSUES_CREATE->value);
    Route::get('/stock-issues/{stockIssue}', [StockIssueController::class, 'show'])
        ->middleware('permission:'.PermissionCode::STOCK_ISSUES_VIEW->value);
    Route::patch('/stock-issues/{stockIssue}', [StockIssueController::class, 'update'])
        ->middleware('permission:'.PermissionCode::STOCK_ISSUES_UPDATE->value);
    Route::post('/stock-issues/{stockIssue}/post', [StockIssueController::class, 'post'])
        ->middleware('permission:'.PermissionCode::STOCK_ISSUES_POST->value);
    Route::post('/stock-issues/{stockIssue}/cancel', [StockIssueController::class, 'cancel'])
        ->middleware('permission:'.PermissionCode::STOCK_ISSUES_CANCEL->value);
    Route::delete('/stock-issues/{stockIssue}', [StockIssueController::class, 'destroy']);

    // Stock Transfers (Fase 5)
    Route::get('/stock-transfers', [StockTransferController::class, 'index'])
        ->middleware('permission:'.PermissionCode::STOCK_TRANSFERS_VIEW->value);
    Route::post('/stock-transfers', [StockTransferController::class, 'store'])
        ->middleware('permission:'.PermissionCode::STOCK_TRANSFERS_CREATE->value);
    Route::get('/stock-transfers/{stockTransfer}', [StockTransferController::class, 'show'])
        ->middleware('permission:'.PermissionCode::STOCK_TRANSFERS_VIEW->value);
    Route::patch('/stock-transfers/{stockTransfer}', [StockTransferController::class, 'update'])
        ->middleware('permission:'.PermissionCode::STOCK_TRANSFERS_UPDATE->value);
    Route::post('/stock-transfers/{stockTransfer}/send', [StockTransferController::class, 'send'])
        ->middleware('permission:'.PermissionCode::STOCK_TRANSFERS_SEND->value);
    Route::post('/stock-transfers/{stockTransfer}/receive', [StockTransferController::class, 'receive'])
        ->middleware('permission:'.PermissionCode::STOCK_TRANSFERS_RECEIVE->value);
    Route::post('/stock-transfers/{stockTransfer}/cancel', [StockTransferController::class, 'cancel'])
        ->middleware('permission:'.PermissionCode::STOCK_TRANSFERS_CANCEL->value);
    Route::delete('/stock-transfers/{stockTransfer}', [StockTransferController::class, 'destroy']);

    // Stock Adjustments (Fase 6A)
    Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])
        ->middleware('permission:'.PermissionCode::STOCK_ADJUSTMENTS_VIEW->value);
    Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])
        ->middleware('permission:'.PermissionCode::STOCK_ADJUSTMENTS_CREATE->value);
    Route::get('/stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'show'])
        ->middleware('permission:'.PermissionCode::STOCK_ADJUSTMENTS_VIEW->value);
    Route::patch('/stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'update'])
        ->middleware('permission:'.PermissionCode::STOCK_ADJUSTMENTS_UPDATE->value);
    Route::post('/stock-adjustments/{stockAdjustment}/post', [StockAdjustmentController::class, 'post'])
        ->middleware('permission:'.PermissionCode::STOCK_ADJUSTMENTS_POST->value);
    Route::post('/stock-adjustments/{stockAdjustment}/cancel', [StockAdjustmentController::class, 'cancel'])
        ->middleware('permission:'.PermissionCode::STOCK_ADJUSTMENTS_CANCEL->value);
    Route::delete('/stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'destroy']);

    // Stock Opnames (Fase 7B)
    Route::get('/stock-opnames', [StockOpnameController::class, 'index'])
        ->middleware('permission:'.PermissionCode::STOCK_OPNAMES_VIEW->value);
    Route::post('/stock-opnames', [StockOpnameController::class, 'store'])
        ->middleware('permission:'.PermissionCode::STOCK_OPNAMES_CREATE->value);
    Route::get('/stock-opnames/{stockOpname}', [StockOpnameController::class, 'show'])
        ->middleware('permission:'.PermissionCode::STOCK_OPNAMES_VIEW->value);
    Route::patch('/stock-opnames/{stockOpname}', [StockOpnameController::class, 'update'])
        ->middleware('permission:'.PermissionCode::STOCK_OPNAMES_UPDATE->value);
    Route::post('/stock-opnames/{stockOpname}/start', [StockOpnameController::class, 'start'])
        ->middleware('permission:'.PermissionCode::STOCK_OPNAMES_START->value);
    Route::patch('/stock-opnames/{stockOpname}/items/{itemId}/count', [StockOpnameController::class, 'count'])
        ->middleware('permission:'.PermissionCode::STOCK_OPNAMES_COUNT->value);
    Route::post('/stock-opnames/{stockOpname}/items', [StockOpnameController::class, 'addUnexpected'])
        ->middleware('permission:'.PermissionCode::STOCK_OPNAMES_COUNT->value);
    Route::post('/stock-opnames/{stockOpname}/complete', [StockOpnameController::class, 'complete'])
        ->middleware('permission:'.PermissionCode::STOCK_OPNAMES_COMPLETE->value);
    Route::post('/stock-opnames/{stockOpname}/reopen', [StockOpnameController::class, 'reopen'])
        ->middleware('permission:'.PermissionCode::STOCK_OPNAMES_REOPEN->value);
    Route::post('/stock-opnames/{stockOpname}/post', [StockOpnameController::class, 'post'])
        ->middleware('permission:'.PermissionCode::STOCK_OPNAMES_POST->value);
    Route::post('/stock-opnames/{stockOpname}/cancel', [StockOpnameController::class, 'cancel'])
        ->middleware('permission:'.PermissionCode::STOCK_OPNAMES_CANCEL->value);

    // Inventory Balance Reconciliation (Admin only)
    Route::get('/inventory/reconciliation/scan', [\App\Features\Inventory\Controllers\InventoryReconciliationController::class, 'scan'])
        ->middleware('permission:'.PermissionCode::INVENTORY_RECONCILE->value);
    Route::post('/inventory/reconciliation/apply', [\App\Features\Inventory\Controllers\InventoryReconciliationController::class, 'apply'])
        ->middleware('permission:'.PermissionCode::INVENTORY_RECONCILE->value);
});
