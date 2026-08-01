<?php

namespace App\Features\Inventory\Actions;

use App\Features\Inventory\Enums\OpnameStatus;
use App\Features\Inventory\Models\StockOpname;
use App\Features\Inventory\Models\StockOpnameReopenLog;
use App\Features\Inventory\Services\InventoryFreezeService;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class ReopenStockOpnameAction
{
    public function __construct(
        private readonly InventoryFreezeService $freezeService
    ) {}

    public function execute(StockOpname $opname, string $reason, int $userId): StockOpname
    {
        $cleanReason = trim($reason);
        if (empty($cleanReason)) {
            throw new DomainException('Alasan pembukaan kembali sesi opname wajib diisi.', 422);
        }

        return DB::transaction(function () use ($opname, $cleanReason, $userId) {
            $lockedOpname = StockOpname::where('id', $opname->id)->lockForUpdate()->first();

            if (! $lockedOpname->isCounted()) {
                throw new DomainException('Hanya dokumen berstatus COUNTED yang dapat dibuka kembali (Reopen).', 409, ['code' => 'INVALID_STATUS_TRANSITION']);
            }

            $this->freezeService->lockAndValidateLocations([$lockedOpname->location_id], $lockedOpname->id);

            // Record immutable reopen log
            StockOpnameReopenLog::create([
                'stock_opname_id' => $lockedOpname->id,
                'reopened_by' => $userId,
                'reason' => $cleanReason,
                'reopened_at' => now(),
            ]);

            // Reset variance_quantity while retaining snapshot & counted_quantity
            $lockedOpname->items()->update(['variance_quantity' => null]);

            $lockedOpname->update([
                'status' => OpnameStatus::IN_PROGRESS,
                'updated_by' => $userId,
            ]);

            return $lockedOpname->fresh(['location', 'items.product.unit', 'reopenLogs.reopener']);
        });
    }
}
