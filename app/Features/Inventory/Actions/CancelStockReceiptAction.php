<?php

namespace App\Features\Inventory\Actions;

use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\ReceiptStatus;
use App\Features\Inventory\Models\StockReceipt;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class CancelStockReceiptAction
{
    public function execute(StockReceipt $receipt, ?int $userId = null): StockReceipt
    {
        return DB::transaction(function () use ($receipt, $userId) {
            $lockedReceipt = clone $receipt;
            $lockedReceipt = StockReceipt::where('id', $lockedReceipt->id)->lockForUpdate()->first();

            if (! $lockedReceipt->isDraft()) {
                throw new DomainException('Only DRAFT receipts can be canceled.', 409);
            }

            // Check authorization
            $locationIds = $lockedReceipt->items->pluck('location_id')->unique()->toArray();
            if ($userId) {
                $user = User::find($userId);
                if ($user && count(array_diff($locationIds, $user->getAllowedLocationIds())) > 0) {
                    throw new DomainException('User is not authorized for one or more locations in this document.', 403);
                }
            }

            $lockedReceipt->update([
                'status' => ReceiptStatus::CANCELED,
            ]);

            return $lockedReceipt;
        });
    }
}
