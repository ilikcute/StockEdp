<?php

use App\Features\Department\Http\Controllers\DepartmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('departments', [DepartmentController::class, 'index']);
    Route::get('departments/active', [DepartmentController::class, 'activeList']);
    Route::post('departments', [DepartmentController::class, 'store']);
    Route::get('departments/{department}', [DepartmentController::class, 'show']);
    Route::put('departments/{department}', [DepartmentController::class, 'update']);
    Route::patch('departments/{department}/status', [DepartmentController::class, 'changeStatus']);
});
