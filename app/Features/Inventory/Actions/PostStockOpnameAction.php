<?php

namespace App\Features\Inventory\Actions;

use App\Features\Auth\Models\User;
use App\Features\Inventory\DTOs\StockChangeDTO;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Enums\OpnameStatus;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockOpname;
use App\Features\Inventory\Policies\StockOpnamePolicy;
use App\Features\Inventory\Services\InventoryFreezeService;
use App\Features\Inventory\Services\StockMovementService;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class PostStockOpnameAction
{
    public function __construct(
        private readonly StockMovementService $stockMovementService,
        private readonly InventoryFreezeService $freezeService,
        private readonly StockOpnamePolicy $policy
    ) {}

    public function execute(StockOpname $opname, int $userId): StockOpname
    {
        return DB::transaction(function () use ($opname, $userId) {
            $lockedOpname = StockOpname::where('id', $opname->id)->lockForUpdate()->first();

            if (! $lockedOpname->isCounted()) {
                throw new DomainException('Hanya dokumen berstatus COUNTED yang dapat diposting.', 409, ['code' => 'INVALID_STATUS_TRANSITION']);
            }

            $user = User::find($userId);
            if (! $user || ! $this->policy->post($user, $lockedOpname)) {
                throw new DomainException(
                    'Anda tidak memiliki hak akses atau melanggar aturan Maker-Checker (pembuat atau penghitung tidak boleh mem-posting).',
                    403,
                    ['code' => 'MAKER_CHECKER_VIOLATION']
                );
            }

            // Verify freeze owner
            $this->freezeService->lockAndValidateLocations([$lockedOpname->location_id], $lockedOpname->id);

            $items = $lockedOpname->items()->orderBy('product_id', 'asc')->lockForUpdate()->get();

            if ($items->isEmpty()) {
                throw new DomainException('Dokumen stock opname tidak memiliki item barang.', 422);
            }

            $dtos = [];
            foreach ($items as $item) {
                if ($item->counted_quantity === null) {
                    throw new DomainException('Terdapat item barang yang belum memiliki hasil kuantitas fisik.', 422, ['code' => 'INCOMPLETE_COUNT']);
                }

                // Verify snapshot drift vs current balance
                $balance = InventoryBalance::where('location_id', $lockedOpname->location_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                $currentBalQty = $balance ? (string) $balance->quantity : '0.0000';
                $snapshotQty = (string) $item->snapshot_quantity;

                if (bccomp($currentBalQty, $snapshotQty, 4) !== 0) {
                    throw new DomainException(
                        "Terjadi pergeseran saldo persediaan (Snapshot Drift) untuk produk ID {$item->product_id}.",
                        409,
                        ['code' => 'OPNAME_SNAPSHOT_DRIFT']
                    );
                }

                // Recalculate variance authoritatively
                $variance = bcsub((string) $item->counted_quantity, $snapshotQty, 4);
                $item->update(['variance_quantity' => $variance]);

                $comp = bccomp($variance, '0.0000', 4);

                if ($comp > 0) {
                    // Positive variance -> OPNAME_IN
                    $dtos[] = new StockChangeDTO(
                        productId: $item->product_id,
                        locationId: $lockedOpname->location_id,
                        quantity: $variance,
                        movementType: MovementType::OPNAME_IN,
                        referenceType: 'App\\Features\\Inventory\\Models\\StockOpname',
                        referenceId: $lockedOpname->id,
                        referenceNumber: $lockedOpname->opname_number,
                        userId: $userId,
                        occurredAt: now()
                    );
                } elseif ($comp < 0) {
                    // Negative variance -> OPNAME_OUT (absolute quantity)
                    $absVariance = bcmul($variance, '-1', 4);
                    $dtos[] = new StockChangeDTO(
                        productId: $item->product_id,
                        locationId: $lockedOpname->location_id,
                        quantity: $absVariance,
                        movementType: MovementType::OPNAME_OUT,
                        referenceType: 'App\\Features\\Inventory\\Models\\StockOpname',
                        referenceId: $lockedOpname->id,
                        referenceNumber: $lockedOpname->opname_number,
                        userId: $userId,
                        occurredAt: now()
                    );
                }
            }

            // Process movements if variance exists (passing $lockedOpname->id as freeze owner)
            if (! empty($dtos)) {
                $this->stockMovementService->recordMultipleMovements($dtos, $lockedOpname->id);
            }

            $lockedOpname->update([
                'status' => OpnameStatus::POSTED,
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            // Unfreeze location
            $this->freezeService->unfreezeLocation($lockedOpname->location_id, $lockedOpname->id);

            return $lockedOpname->fresh(['location', 'items.product.unit', 'poster']);
        });
    }
}
