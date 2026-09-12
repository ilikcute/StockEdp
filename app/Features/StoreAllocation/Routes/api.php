<?php

use App\Features\StoreAllocation\Http\Controllers\StoreAllocationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('store-allocations', [StoreAllocationController::class, 'index']);
    Route::post('store-allocations', [StoreAllocationController::class, 'store']);
    Route::get('store-allocations/{storeAllocation}', [StoreAllocationController::class, 'show']);
});
