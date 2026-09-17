<?php

use App\Features\Rma\Http\Controllers\VendorRmaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('vendor-rmas')->group(function () {
    Route::get('/', [VendorRmaController::class, 'index']);
    Route::post('/', [VendorRmaController::class, 'store']);
    Route::get('/{id}', [VendorRmaController::class, 'show']);
    Route::post('/{id}/dispatch', [VendorRmaController::class, 'dispatch']);
    Route::post('/{id}/complete', [VendorRmaController::class, 'complete']);
    Route::post('/{id}/cancel', [VendorRmaController::class, 'cancel']);
});
