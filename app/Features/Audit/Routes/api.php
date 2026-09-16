<?php

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Audit\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:'.PermissionCode::AUDIT_LOGS_VIEW->value])
    ->prefix('audit-logs')
    ->group(function (): void {
        Route::get('/', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('modules', [AuditLogController::class, 'modules'])->name('audit-logs.modules');
    });