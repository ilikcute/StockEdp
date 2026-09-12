<?php

use App\Features\User\Controllers\RolePermissionController;
use App\Features\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:users.manage'])->group(function (): void {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/form-options', [UserController::class, 'formOptions'])->name('users.form-options');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.update-status');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');

    Route::get('/roles', [RolePermissionController::class, 'roles'])->name('roles.index');
    Route::put('/roles/{role}/permissions', [RolePermissionController::class, 'updatePermissions'])->name('roles.update-permissions');
    Route::get('/permissions', [RolePermissionController::class, 'permissions'])->name('permissions.index');
});
