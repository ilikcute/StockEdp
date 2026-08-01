<?php

namespace App\Features\Inventory\Models;

// Move or keep policy in App\Features\Inventory\Policies\StockAdjustmentPolicy

namespace App\Features\Inventory\Policies;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\User;
use App\Features\Inventory\Models\StockAdjustment;

class StockAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionCode::STOCK_ADJUSTMENTS_VIEW->value);
    }

    public function view(User $user, StockAdjustment $adjustment): bool
    {
        if (! $user->hasPermissionTo(PermissionCode::STOCK_ADJUSTMENTS_VIEW->value)) {
            return false;
        }

        return in_array($adjustment->location_id, $user->getAllowedLocationIds(), true);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionCode::STOCK_ADJUSTMENTS_CREATE->value);
    }

    public function update(User $user, StockAdjustment $adjustment): bool
    {
        if (! $user->hasPermissionTo(PermissionCode::STOCK_ADJUSTMENTS_UPDATE->value)) {
            return false;
        }

        if (! $adjustment->isDraft()) {
            return false;
        }

        if (! in_array($adjustment->location_id, $user->getAllowedLocationIds(), true)) {
            return false;
        }

        // Ownership rule: Petugas Gudang (WAREHOUSE_OFFICER) hanya boleh update draft miliknya sendiri
        if ($user->hasRole(RoleCode::WAREHOUSE_OFFICER->value) && ! $user->hasRole(RoleCode::ADMIN->value) && ! $user->hasRole(RoleCode::INVENTORY_SUPERVISOR->value)) {
            return $adjustment->created_by === $user->id;
        }

        return true;
    }

    public function post(User $user, StockAdjustment $adjustment): bool
    {
        if (! $user->hasPermissionTo(PermissionCode::STOCK_ADJUSTMENTS_POST->value)) {
            return false;
        }

        if (! $adjustment->isDraft()) {
            return false;
        }

        if (! in_array($adjustment->location_id, $user->getAllowedLocationIds(), true)) {
            return false;
        }

        // Maker-Checker: Pembuat tidak boleh mem-posting dokumen miliknya sendiri (termasuk Admin & Supervisor)
        if ($adjustment->created_by === $user->id) {
            return false;
        }

        return true;
    }

    public function cancel(User $user, StockAdjustment $adjustment): bool
    {
        if (! $user->hasPermissionTo(PermissionCode::STOCK_ADJUSTMENTS_CANCEL->value)) {
            return false;
        }

        if (! $adjustment->isDraft()) {
            return false;
        }

        if (! in_array($adjustment->location_id, $user->getAllowedLocationIds(), true)) {
            return false;
        }

        // Ownership rule: Petugas Gudang hanya boleh cancel draft miliknya sendiri
        if ($user->hasRole(RoleCode::WAREHOUSE_OFFICER->value) && ! $user->hasRole(RoleCode::ADMIN->value) && ! $user->hasRole(RoleCode::INVENTORY_SUPERVISOR->value)) {
            return $adjustment->created_by === $user->id;
        }

        return true;
    }
}
