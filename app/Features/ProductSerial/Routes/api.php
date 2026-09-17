<?php

use App\Features\Auth\Enums\PermissionCode;
use App\Features\ProductSerial\Http\Controllers\ProductSerialController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('product-serials/lookup', [ProductSerialController::class, 'lookup'])
        ->middleware('permission:'.PermissionCode::PRODUCT_SERIALS_VIEW->value);

    Route::get('product-serials', [ProductSerialController::class, 'index'])
        ->middleware('permission:'.PermissionCode::PRODUCT_SERIALS_VIEW->value);

    Route::get('product-serials/{id}', [ProductSerialController::class, 'show'])
        ->middleware('permission:'.PermissionCode::PRODUCT_SERIALS_VIEW->value);
});
