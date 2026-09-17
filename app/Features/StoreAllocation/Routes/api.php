<?php

use App\Features\Auth\Enums\PermissionCode;
use App\Features\StoreAllocation\Http\Controllers\StoreAllocationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('store-allocations', [StoreAllocationController::class, 'index'])
        ->middleware('permission:'.PermissionCode::STORE_ALLOCATIONS_VIEW->value);

    Route::post('store-allocations', [StoreAllocationController::class, 'store'])
        ->middleware('permission:'.PermissionCode::STORE_ALLOCATIONS_CREATE_OWN->value.'|'.PermissionCode::STORE_ALLOCATIONS_CREATE_FOR_OTHERS->value);

    Route::get('store-allocations/{storeAllocation}', [StoreAllocationController::class, 'show'])
        ->middleware('permission:'.PermissionCode::STORE_ALLOCATIONS_VIEW->value);
});
