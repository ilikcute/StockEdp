<?php

namespace App\Features\Inventory\Actions;

use App\Features\Inventory\Enums\OpnameStatus;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockOpname;
use App\Features\Inventory\Services\InventoryFreezeService;
use App\Features\Location\Models\Location;
use App\Shared\Exceptions\DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class StartStockOpnameAction
{
    public function __construct(
        private readonly InventoryFreezeService $freezeService
    ) {}

    public function execute(StockOpname $opname, int $userId): StockOpname
    {
        try {
            return DB::transaction(function () use ($opname, $userId) {
                $lockedOpname = StockOpname::where('id', $opname->id)->lockForUpdate()->first();

                if (! $lockedOpname->isDraft()) {
                    throw new DomainException('Hanya dokumen berstatus DRAFT yang dapat dimulai.', 409, ['code' => 'INVALID_STATUS_TRANSITION']);
                }

                $location = Location::find($lockedOpname->location_id);
                if (! $location || ! $location->is_active) {
                    throw new DomainException('Lokasi persediaan tidak aktif.', 422);
                }

                // Freeze location (will throw 409 if already frozen)
                $this->freezeService->freezeLocation($lockedOpname->location_id, $lockedOpname->id);

                // Take balance snapshot
                $balances = InventoryBalance::where('location_id', $lockedOpname->location_id)->get();

                foreach ($balances as $balance) {
                    $lockedOpname->items()->create([
                        'product_id' => $balance->product_id,
                        'snapshot_quantity' => (string) $balance->quantity,
                        'counted_quantity' => null,
                        'variance_quantity' => null,
                        'count_version' => 0,
                        'is_unexpected' => false,
                    ]);
                }

                $lockedOpname->update([
                    'status' => OpnameStatus::IN_PROGRESS,
                    'started_by' => $userId,
                    'started_at' => now(),
                ]);

                return $lockedOpname->fresh(['location', 'items.product.unit', 'starter']);
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'unique_active_opname_per_location')) {
                throw new DomainException('Sudah ada sesi Stock Opname aktif untuk lokasi ini.', 409, ['code' => 'OPNAME_ALREADY_ACTIVE']);
            }
            throw $e;
        }
    }
}
