<?php

namespace App\Features\Inventory\Policies;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Models\User;
use App\Features\Inventory\Models\StockOpname;
use Illuminate\Support\Facades\DB;

class StockOpnamePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionCode::STOCK_OPNAMES_VIEW->value);
    }

    public function view(User $user, StockOpname $opname): bool
    {
        if (! $user->hasPermissionTo(PermissionCode::STOCK_OPNAMES_VIEW->value)) {
            return false;
        }

        return in_array($opname->location_id, $user->getAllowedLocationIds(), true);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionCode::STOCK_OPNAMES_CREATE->value);
    }

    public function update(User $user, StockOpname $opname): bool
    {
        if (! $user->hasPermissionTo(PermissionCode::STOCK_OPNAMES_UPDATE->value)) {
            return false;
        }

        if (! $opname->isDraft()) {
            return false;
        }

        return in_array($opname->location_id, $user->getAllowedLocationIds(), true);
    }

    public function start(User $user, StockOpname $opname): bool
    {
        if (! $user->hasPermissionTo(PermissionCode::STOCK_OPNAMES_START->value)) {
            return false;
        }

        if (! $opname->isDraft()) {
            return false;
        }

        return in_array($opname->location_id, $user->getAllowedLocationIds(), true);
    }

    public function count(User $user, StockOpname $opname): bool
    {
        if (! $user->hasPermissionTo(PermissionCode::STOCK_OPNAMES_COUNT->value)) {
            return false;
        }

        if (! $opname->isInProgress()) {
            return false;
        }

        return in_array($opname->location_id, $user->getAllowedLocationIds(), true);
    }

    public function addUnexpected(User $user, StockOpname $opname): bool
    {
        return $this->count($user, $opname);
    }

    public function complete(User $user, StockOpname $opname): bool
    {
        if (! $user->hasPermissionTo(PermissionCode::STOCK_OPNAMES_COMPLETE->value)) {
            return false;
        }

        if (! $opname->isInProgress()) {
            return false;
        }

        return in_array($opname->location_id, $user->getAllowedLocationIds(), true);
    }

    public function reopen(User $user, StockOpname $opname): bool
    {
        if (! $user->hasPermissionTo(PermissionCode::STOCK_OPNAMES_REOPEN->value)) {
            return false;
        }

        if (! $opname->isCounted()) {
            return false;
        }

        return in_array($opname->location_id, $user->getAllowedLocationIds(), true);
    }

    public function post(User $user, StockOpname $opname): bool
    {
        if (! $user->hasPermissionTo(PermissionCode::STOCK_OPNAMES_POST->value)) {
            return false;
        }

        if (! $opname->isCounted()) {
            return false;
        }

        if (! in_array($opname->location_id, $user->getAllowedLocationIds(), true)) {
            return false;
        }

        // Maker-Checker: Creator cannot post
        if ($opname->created_by === $user->id) {
            return false;
        }

        // Maker-Checker: Counter participant cannot post
        $itemIds = $opname->items()->pluck('id');
        if (! $itemIds->isEmpty()) {
            $isCounterParticipant = DB::table('stock_opname_count_logs')
                ->whereIn('stock_opname_item_id', $itemIds)
                ->where('user_id', $user->id)
                ->exists();

            if ($isCounterParticipant) {
                return false;
            }
        }

        return true;
    }

    public function cancel(User $user, StockOpname $opname): bool
    {
        if (! $user->hasPermissionTo(PermissionCode::STOCK_OPNAMES_CANCEL->value)) {
            return false;
        }

        if ($opname->isPosted() || $opname->isCanceled()) {
            return false;
        }

        return in_array($opname->location_id, $user->getAllowedLocationIds(), true);
    }
}
