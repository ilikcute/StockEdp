<?php

namespace App\Features\Inventory\Actions;

use App\Features\Inventory\Enums\OpnameStatus;
use App\Features\Inventory\Models\StockOpname;
use App\Features\Inventory\Services\InventoryFreezeService;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class CancelStockOpnameAction
{
    public function __construct(
        private readonly InventoryFreezeService $freezeService
    ) {}

    public function execute(StockOpname $opname, string $cancelReason, int $userId): StockOpname
    {
        $cleanReason = trim($cancelReason);
        if (empty($cleanReason)) {
            throw new DomainException('Alasan pembatalan dokumen stock opname wajib diisi.', 422);
        }

        return DB::transaction(function () use ($opname, $cleanReason, $userId) {
            $lockedOpname = StockOpname::where('id', $opname->id)->lockForUpdate()->first();

            if ($lockedOpname->isPosted() || $lockedOpname->isCanceled()) {
                throw new DomainException('Dokumen yang sudah diposting atau dibatalkan tidak dapat diubah.', 409, ['code' => 'INVALID_STATUS_TRANSITION']);
            }

            // Unfreeze location if it was in IN_PROGRESS or COUNTED
            if ($lockedOpname->isInProgress() || $lockedOpname->isCounted()) {
                $this->freezeService->unfreezeLocation($lockedOpname->location_id, $lockedOpname->id);
            }

            $lockedOpname->update([
                'status' => OpnameStatus::CANCELED,
                'cancel_reason' => $cleanReason,
                'canceled_by' => $userId,
                'canceled_at' => now(),
            ]);

            return $lockedOpname->fresh(['location', 'items.product.unit', 'canceler']);
        });
    }
}
