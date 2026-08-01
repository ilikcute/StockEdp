<?php

namespace App\Features\Inventory\Actions;

use App\Features\Inventory\Models\StockOpname;
use App\Features\Inventory\Models\StockOpnameCountLog;
use App\Features\Inventory\Models\StockOpnameItem;
use App\Features\Inventory\Services\InventoryFreezeService;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class InputCountAction
{
    public function __construct(
        private readonly InventoryFreezeService $freezeService
    ) {}

    public function execute(StockOpname $opname, int $itemId, array $data, int $userId): StockOpnameItem
    {
        return DB::transaction(function () use ($opname, $itemId, $data, $userId) {
            $lockedOpname = StockOpname::where('id', $opname->id)->lockForUpdate()->first();

            if (! $lockedOpname->isInProgress()) {
                throw new DomainException('Perhitungan fisik hanya dapat dilakukan saat opname berstatus IN_PROGRESS.', 409, ['code' => 'INVALID_STATUS_TRANSITION']);
            }

            // Verify freeze owner
            $this->freezeService->lockAndValidateLocations([$lockedOpname->location_id], $lockedOpname->id);

            $item = StockOpnameItem::where('id', $itemId)
                ->where('stock_opname_id', $lockedOpname->id)
                ->lockForUpdate()
                ->first();

            if (! $item) {
                throw new DomainException('Item stock opname tidak ditemukan.', 404);
            }

            // Optimistic concurrency check
            if (isset($data['expected_version']) && (int) $data['expected_version'] !== (int) $item->count_version) {
                throw new DomainException('Data perhitungan item telah diperbarui oleh pengguna lain. Silakan muat ulang.', 409, ['code' => 'COUNT_VERSION_CONFLICT']);
            }

            $countedQuantity = (string) $data['counted_quantity'];
            if (bccomp($countedQuantity, '0.0000', 4) < 0) {
                throw new DomainException('Kuantitas fisik tidak boleh negatif.', 422);
            }

            $previousQuantity = $item->counted_quantity;
            $newVersion = $item->count_version + 1;

            // Create immutable count log
            StockOpnameCountLog::create([
                'stock_opname_item_id' => $item->id,
                'user_id' => $userId,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $countedQuantity,
                'count_version' => $newVersion,
                'created_at' => now(),
            ]);

            $item->update([
                'counted_quantity' => $countedQuantity,
                'count_version' => $newVersion,
                'counted_by' => $userId,
                'counted_at' => now(),
                'item_notes' => isset($data['item_notes']) ? trim($data['item_notes']) : $item->item_notes,
            ]);

            return $item->fresh(['product.unit', 'counter']);
        });
    }
}
