<?php

namespace App\Features\Inventory\Actions;

use App\Features\Inventory\Enums\OpnameStatus;
use App\Features\Inventory\Models\StockOpname;
use App\Features\Inventory\Services\InventoryFreezeService;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class CompleteStockOpnameAction
{
    public function __construct(
        private readonly InventoryFreezeService $freezeService
    ) {}

    public function execute(StockOpname $opname, int $userId): StockOpname
    {
        return DB::transaction(function () use ($opname, $userId) {
            $lockedOpname = StockOpname::where('id', $opname->id)->lockForUpdate()->first();

            if (! $lockedOpname->isInProgress()) {
                throw new DomainException('Hanya dokumen berstatus IN_PROGRESS yang dapat diselesaikan.', 409, ['code' => 'INVALID_STATUS_TRANSITION']);
            }

            $this->freezeService->lockAndValidateLocations([$lockedOpname->location_id], $lockedOpname->id);

            $items = $lockedOpname->items()->orderBy('product_id', 'asc')->lockForUpdate()->get();

            if ($items->isEmpty()) {
                throw new DomainException('Dokumen stock opname tidak memiliki item barang.', 422);
            }

            foreach ($items as $item) {
                if ($item->counted_quantity === null) {
                    throw new DomainException(
                        'Seluruh item barang wajib dihitung fisik sebelum opname diselesaikan.',
                        422,
                        ['code' => 'INCOMPLETE_COUNT']
                    );
                }

                $variance = bcsub((string) $item->counted_quantity, (string) $item->snapshot_quantity, 4);

                $item->update([
                    'variance_quantity' => $variance,
                ]);
            }

            $lockedOpname->update([
                'status' => OpnameStatus::COUNTED,
                'completed_by' => $userId,
                'completed_at' => now(),
            ]);

            return $lockedOpname->fresh(['location', 'items.product.unit', 'completer']);
        });
    }
}
