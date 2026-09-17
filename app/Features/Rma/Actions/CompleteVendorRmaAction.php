<?php

namespace App\Features\Rma\Actions;

use App\Features\Inventory\DTOs\StockChangeDTO;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Enums\StockCondition;
use App\Features\Inventory\Services\InventoryFreezeService;
use App\Features\Inventory\Services\StockMovementService;
use App\Features\MonthEnd\Services\PeriodLockService;
use App\Features\ProductSerial\Services\ProductSerialService;
use App\Features\Rma\Enums\RmaItemStatus;
use App\Features\Rma\Enums\RmaStatus;
use App\Features\Rma\Models\VendorRma;
use App\Shared\Exceptions\DomainException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CompleteVendorRmaAction
{
    public function __construct(
        private readonly StockMovementService $stockMovementService,
        private readonly ProductSerialService $productSerialService,
        private readonly PeriodLockService $periodLockService,
        private readonly InventoryFreezeService $freezeService
    ) {}

    public function execute(VendorRma $rma, array $data, int $userId): VendorRma
    {
        return DB::transaction(function () use ($rma, $data, $userId) {
            $lockedRma = VendorRma::where('id', $rma->id)->lockForUpdate()->firstOrFail();

            if (! $lockedRma->isDispatched()) {
                throw new DomainException("Hanya dokumen RMA berstatus DISPATCHED yang dapat diselesaikan (status saat ini: {$lockedRma->status->value}).", 409);
            }

            $date = Carbon::now()->toDateString();
            $this->periodLockService->ensureDateIsOpen($date, 'menerima pengembalian RMA dari vendor');

            // Destination location for serviced/repaired unit (defaults to origin location or provided location_id)
            $destinationLocationId = (int) ($data['destination_location_id'] ?? $lockedRma->origin_location_id);

            // Global Lock Order
            $this->freezeService->lockAndValidateLocations([$destinationLocationId]);

            $lockedRma->load(['items.product', 'supplier', 'originLocation']);

            $dtos = [];
            $productQuantities = [];

            foreach ($lockedRma->items as $item) {
                $pid = $item->product_id;
                $productQuantities[$pid] = ($productQuantities[$pid] ?? 0) + $item->quantity;
            }

            foreach ($productQuantities as $productId => $qty) {
                $dtos[] = new StockChangeDTO(
                    productId: $productId,
                    locationId: $destinationLocationId,
                    quantity: (string) $qty,
                    movementType: MovementType::RECEIPT_GA,
                    referenceType: VendorRma::class,
                    referenceId: $lockedRma->id,
                    referenceNumber: $lockedRma->rma_number,
                    userId: $userId,
                    occurredAt: Carbon::now()->toDateTimeString(),
                    condition: StockCondition::GOOD
                );
            }

            // Increase GOOD inventory balance
            $this->stockMovementService->recordMultipleMovements($dtos);

            // Transition serials back to IN_STOCK (GOOD)
            foreach ($lockedRma->items as $item) {
                $itemStatus = RmaItemStatus::SERVICED;
                if (! empty($item->serial_number)) {
                    $this->productSerialService->recordRmaReturn(
                        serialNumber: $item->serial_number,
                        productId: $item->product_id,
                        toLocationId: $destinationLocationId,
                        referenceType: VendorRma::class,
                        referenceId: $lockedRma->id,
                        referenceNumber: $lockedRma->rma_number,
                        userId: $userId,
                        notes: "Unit selesai diservis vendor {$lockedRma->supplier->name} (RMA #{$lockedRma->rma_number})",
                        occurredAt: Carbon::now()
                    );
                }

                $item->update(['status' => $itemStatus]);
            }

            $lockedRma->update([
                'status' => RmaStatus::COMPLETED,
                'notes' => trim(($lockedRma->notes ? $lockedRma->notes . "\n" : '') . ($data['notes'] ?? 'Selesai diservis dan diterima kembali ke gudang.')),
            ]);

            return $lockedRma->fresh(['supplier', 'originLocation', 'items.product', 'items.productSerial', 'creator']);
        });
    }
}
