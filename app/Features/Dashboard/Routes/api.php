<?php

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Dashboard\Http\Controllers\OperationalDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:' . PermissionCode::DASHBOARD_VIEW->value])->group(function () {
    Route::get('dashboard', [OperationalDashboardController::class, 'index'])
        ->name('dashboard.index');
    Route::get('dashboard/inventory-movement-summary', [OperationalDashboardController::class, 'movementSummary'])
        ->name('dashboard.inventory-movement-summary');
});
