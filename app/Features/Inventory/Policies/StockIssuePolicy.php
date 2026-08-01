<?php

namespace App\Features\Inventory\Policies;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Models\User;
use App\Features\Inventory\Models\StockIssue;

class StockIssuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionCode::STOCK_ISSUES_VIEW->value);
    }

    private function hasLocationAccess(User $user, StockIssue $issue): bool
    {
        $issue->loadMissing('items');
        if ($issue->items->isEmpty()) {
            return true;
        }
        $locationIds = $issue->items->pluck('location_id')->unique()->toArray();
        $allowedLocations = $user->getAllowedLocationIds();

        return empty(array_diff($locationIds, $allowedLocations));
    }

    public function view(User $user, StockIssue $stockIssue): bool
    {
        return $user->hasPermissionTo(PermissionCode::STOCK_ISSUES_VIEW->value) && $this->hasLocationAccess($user, $stockIssue);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionCode::STOCK_ISSUES_CREATE->value);
    }

    public function update(User $user, StockIssue $stockIssue): bool
    {
        return $user->hasPermissionTo(PermissionCode::STOCK_ISSUES_UPDATE->value) && $this->hasLocationAccess($user, $stockIssue);
    }

    public function post(User $user, StockIssue $stockIssue): bool
    {
        return $user->hasPermissionTo(PermissionCode::STOCK_ISSUES_POST->value) && $this->hasLocationAccess($user, $stockIssue);
    }

    public function cancel(User $user, StockIssue $stockIssue): bool
    {
        return $user->hasPermissionTo(PermissionCode::STOCK_ISSUES_CANCEL->value) && $this->hasLocationAccess($user, $stockIssue);
    }
}
