<?php

namespace App\Features\Rma\Actions;

use App\Features\Inventory\DTOs\StockChangeDTO;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Enums\StockCondition;
use App\Features\Inventory\Services\InventoryFreezeService;
use App\Features\Inventory\Services\StockMovementService;
use App\Features\MonthEnd\Services\PeriodLockService;
use App\Features\ProductSerial\Services\ProductSerialService;
use App\Features\Rma\Enums\RmaStatus;
use App\Features\Rma\Models\VendorRma;
use App\Shared\Exceptions\DomainException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DispatchVendorRmaAction
{
    public function __construct(
        private readonly StockMovementService $stockMovementService,
        private readonly ProductSerialService $productSerialService,
        private readonly PeriodLockService $periodLockService,
        private readonly InventoryFreezeService $freezeService
    ) {}

    public function execute(VendorRma $rma, int $userId): VendorRma
    {
        return DB::transaction(function () use ($rma, $userId) {
            $lockedRma = VendorRma::where('id', $rma->id)->lockForUpdate()->firstOrFail();

            if (! $lockedRma->isDraft()) {
                throw new DomainException("Hanya dokumen RMA berstatus DRAFT yang dapat dikirim (status saat ini: {$lockedRma->status->value}).", 409);
            }

            $date = Carbon::now()->toDateString();
            $this->periodLockService->ensureDateIsOpen($date, 'mengirim (dispatch) RMA ke vendor');

            $lockedRma->load(['items.product', 'supplier', 'originLocation']);
            if ($lockedRma->items->isEmpty()) {
                throw new DomainException('Dokumen RMA tidak memiliki item untuk dikirim.', 422);
            }

            // Global Lock Order: Lock origin location
            $this->freezeService->lockAndValidateLocations([$lockedRma->origin_location_id]);

            $dtos = [];
            // Group quantities per product for DEFECTIVE condition balance deduction
            $productQuantities = [];
            foreach ($lockedRma->items as $item) {
                $pid = $item->product_id;
                $productQuantities[$pid] = ($productQuantities[$pid] ?? 0) + $item->quantity;
            }

            foreach ($productQuantities as $productId => $qty) {
                $dtos[] = new StockChangeDTO(
                    productId: $productId,
                    locationId: $lockedRma->origin_location_id,
                    quantity: (string) $qty,
                    movementType: MovementType::RMA_DISPATCH,
                    referenceType: VendorRma::class,
                    referenceId: $lockedRma->id,
                    referenceNumber: $lockedRma->rma_number,
                    userId: $userId,
                    occurredAt: Carbon::now()->toDateTimeString(),
                    condition: StockCondition::DEFECTIVE
                );
            }

            // Deduct defective inventory balance
            $this->stockMovementService->recordMultipleMovements($dtos);

            // Update serial number lifecycle tracking if items contain serials
            foreach ($lockedRma->items as $item) {
                if (! empty($item->serial_number)) {
                    $this->productSerialService->recordRmaDispatch(
                        serialNumber: $item->serial_number,
                        productId: $item->product_id,
                        fromLocationId: $lockedRma->origin_location_id,
                        referenceType: VendorRma::class,
                        referenceId: $lockedRma->id,
                        referenceNumber: $lockedRma->rma_number,
                        userId: $userId,
                        notes: "Klaim RMA Vendor #{$lockedRma->rma_number} ke {$lockedRma->supplier->name}: {$item->fault_description}",
                        occurredAt: Carbon::now()
                    );
                }
            }

            $lockedRma->update([
                'status' => RmaStatus::DISPATCHED,
                'dispatch_date' => Carbon::now()->toDateString(),
            ]);

            return $lockedRma->fresh(['supplier', 'originLocation', 'items.product', 'items.productSerial', 'creator']);
        });
    }
}
