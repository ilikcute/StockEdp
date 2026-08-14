<?php

use App\Features\Replenishment\Http\Controllers\ReplenishmentRecommendationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('replenishment-recommendations/filter-options', [ReplenishmentRecommendationController::class, 'filterOptions'])
        ->name('replenishment-recommendations.filter-options');
    Route::get('replenishment-recommendations', [ReplenishmentRecommendationController::class, 'index'])
        ->name('replenishment-recommendations.index');
});
