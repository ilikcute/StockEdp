<?php

use App\Features\Unit\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('units', [UnitController::class, 'index']);
    Route::post('units', [UnitController::class, 'store']);
    Route::get('units/{unit}', [UnitController::class, 'show']);
    Route::put('units/{unit}', [UnitController::class, 'update']);
    Route::patch('units/{unit}/status', [UnitController::class, 'changeStatus']);
});
