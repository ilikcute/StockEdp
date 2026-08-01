<?php

use App\Features\Health\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthCheckController::class)->name('health.check');
