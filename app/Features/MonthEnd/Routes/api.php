<?php

use App\Features\MonthEnd\Http\Controllers\MonthEndController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('month-end')->group(function () {
    Route::get('periods', [MonthEndController::class, 'index']);
    Route::get('summary', [MonthEndController::class, 'summary']);
    Route::get('approaching-alert', [MonthEndController::class, 'approachingAlert']);
    Route::get('periods/{id}/snapshots', [MonthEndController::class, 'snapshots']);
    Route::post('periods/close', [MonthEndController::class, 'close']);
    Route::post('periods/{id}/reopen', [MonthEndController::class, 'reopen']);
});
