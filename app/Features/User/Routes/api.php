<?php

use App\Features\User\Controllers\RolePermissionController;
use App\Features\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:users.manage'])->group(function (): void {
    Route::get('/users/form-options', [UserController::class, 'formOptions'])->name('users.form-options');
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.update-status');
    Route::apiResource('users', UserController::class);

    Route::get('/roles', [RolePermissionController::class, 'roles'])->name('roles.index');
    Route::put('/roles/{role}/permissions', [RolePermissionController::class, 'updatePermissions'])->name('roles.update-permissions');
    Route::get('/permissions', [RolePermissionController::class, 'permissions'])->name('permissions.index');
});
