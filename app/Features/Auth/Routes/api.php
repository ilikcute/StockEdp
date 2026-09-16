<?php

use App\Features\Auth\Http\Controllers\LoginController;
use App\Features\Auth\Http\Controllers\LogoutController;
use App\Features\Auth\Http\Controllers\MeController;
use App\Features\Auth\Http\Controllers\ProfileController;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->middleware([StartSession::class])->group(function (): void {
    Route::post('/login', LoginController::class)
        ->middleware('guest')
        ->name('auth.login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', MeController::class)->name('auth.me');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('auth.profile.update');
        Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('auth.profile.update-password');
        Route::post('/logout', LogoutController::class)->name('auth.logout');
    });
});
