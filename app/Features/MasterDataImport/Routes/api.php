<?php

use App\Features\MasterDataImport\Http\Controllers\MasterDataImportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('master-data-import')->group(function (): void {
    Route::get('{type}/template', [MasterDataImportController::class, 'template']);
    Route::post('{type}/validate', [MasterDataImportController::class, 'validate']);
    Route::post('{type}/commit', [MasterDataImportController::class, 'commit']);
});
