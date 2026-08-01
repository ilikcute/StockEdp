<?php

namespace App\Features\Inventory\Policies;

use App\Features\Auth\Models\User;
use App\Features\Inventory\Models\StockTransfer;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockTransferPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('stock_transfers.view');
    }

    public function view(User $user, StockTransfer $transfer): bool
    {
        if (! $user->hasPermissionTo('stock_transfers.view')) {
            return false;
        }

        $allowedLocations = $user->getAllowedLocationIds();

        return in_array($transfer->origin_location_id, $allowedLocations)
            || in_array($transfer->destination_location_id, $allowedLocations);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('stock_transfers.create');
    }

    public function update(User $user, StockTransfer $transfer): bool
    {
        if (! $user->hasPermissionTo('stock_transfers.update')) {
            return false;
        }

        return in_array($transfer->origin_location_id, $user->getAllowedLocationIds());
    }

    public function send(User $user, StockTransfer $transfer): bool
    {
        if (! $user->hasPermissionTo('stock_transfers.send')) {
            return false;
        }

        return in_array($transfer->origin_location_id, $user->getAllowedLocationIds());
    }

    public function receive(User $user, StockTransfer $transfer): bool
    {
        if (! $user->hasPermissionTo('stock_transfers.receive')) {
            return false;
        }

        return in_array($transfer->destination_location_id, $user->getAllowedLocationIds());
    }

    public function cancel(User $user, StockTransfer $transfer): bool
    {
        if (! $user->hasPermissionTo('stock_transfers.cancel')) {
            return false;
        }

        return in_array($transfer->origin_location_id, $user->getAllowedLocationIds());
    }
}
