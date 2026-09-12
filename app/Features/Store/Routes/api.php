<?php

use App\Features\Store\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('stores', [StoreController::class, 'index']);
    Route::post('stores', [StoreController::class, 'store']);
    Route::get('stores/{store}', [StoreController::class, 'show']);
    Route::put('stores/{store}', [StoreController::class, 'update']);
    Route::patch('stores/{store}/status', [StoreController::class, 'changeStatus']);
});
