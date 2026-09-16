<?php

namespace App\Features\Inventory\Policies;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Models\User;
use App\Features\Inventory\Models\StockReceipt;

class StockReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionCode::STOCK_RECEIPTS_VIEW->value);
    }

    private function hasLocationAccess(User $user, StockReceipt $receipt): bool
    {
        $receipt->loadMissing('items');
        if ($receipt->items->isEmpty()) {
            return true;
        }
        $locationIds = $receipt->items->pluck('location_id')->unique()->toArray();
        $allowedLocations = $user->getAllowedLocationIds();

        return empty(array_diff($locationIds, $allowedLocations));
    }

    public function view(User $user, StockReceipt $stockReceipt): bool
    {
        return $user->hasPermissionTo(PermissionCode::STOCK_RECEIPTS_VIEW->value) && $this->hasLocationAccess($user, $stockReceipt);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionCode::STOCK_RECEIPTS_CREATE->value);
    }

    public function update(User $user, StockReceipt $stockReceipt): bool
    {
        return $user->hasPermissionTo(PermissionCode::STOCK_RECEIPTS_UPDATE->value) && $this->hasLocationAccess($user, $stockReceipt);
    }

    public function post(User $user, StockReceipt $stockReceipt): bool
    {
        return $user->hasPermissionTo(PermissionCode::STOCK_RECEIPTS_POST->value) && $this->hasLocationAccess($user, $stockReceipt);
    }

    public function cancel(User $user, StockReceipt $stockReceipt): bool
    {
        return $user->hasPermissionTo(PermissionCode::STOCK_RECEIPTS_CANCEL->value) && $this->hasLocationAccess($user, $stockReceipt);
    }
}