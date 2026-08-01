<?php

namespace App\Features\Inventory\Actions;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\AdjustmentStatus;
use App\Features\Inventory\Models\StockAdjustment;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class CancelStockAdjustmentAction
{
    public function execute(StockAdjustment $adjustment, int $userId): StockAdjustment
    {
        return DB::transaction(function () use ($adjustment, $userId) {
            $lockedAdjustment = StockAdjustment::where('id', $adjustment->id)->lockForUpdate()->first();

            if (! $lockedAdjustment || ! $lockedAdjustment->isDraft()) {
                throw new DomainException('Hanya adjustment berstatus DRAFT yang dapat dibatalkan.', 409);
            }

            $user = User::find($userId);
            if ($user && $user->hasRole(RoleCode::WAREHOUSE_OFFICER->value) && ! $user->hasRole(RoleCode::ADMIN->value) && ! $user->hasRole(RoleCode::INVENTORY_SUPERVISOR->value)) {
                if ($lockedAdjustment->created_by !== $userId) {
                    throw new DomainException('Petugas gudang hanya dapat membatalkan draft adjustment miliknya sendiri.', 403);
                }
            }

            $lockedAdjustment->update([
                'status' => AdjustmentStatus::CANCELED,
                'canceled_by' => $userId,
                'canceled_at' => now(),
            ]);

            return $lockedAdjustment;
        });
    }
}
