<?php

namespace App\Features\Inventory\Actions;

use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Inventory\Models\StockTransfer;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class CancelStockTransferAction
{
    public function execute(StockTransfer $transfer, ?int $userId = null): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $userId) {
            $lockedTransfer = StockTransfer::where('id', $transfer->id)->lockForUpdate()->first();

            if (! $lockedTransfer->isDraft()) {
                throw new DomainException('Only DRAFT transfers can be canceled.', 409);
            }

            $lockedTransfer->update([
                'status' => TransferStatus::CANCELED,
                'canceled_by' => $userId,
                'canceled_at' => now(),
            ]);

            return $lockedTransfer;
        });
    }
}
