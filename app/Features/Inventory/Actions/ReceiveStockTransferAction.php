<?php

namespace App\Features\Inventory\Actions;

use App\Features\Auth\Models\User;
use App\Features\Inventory\DTOs\StockChangeDTO;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Inventory\Services\StockMovementService;
use App\Features\Location\Models\Location;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class ReceiveStockTransferAction
{
    public function __construct(
        private readonly StockMovementService $stockMovementService
    ) {}

    /**
     * @param  array<int, string|float|int>  $receivedQuantities  Map of stock_transfer_item id => received quantity.
     */
    public function execute(StockTransfer $transfer, ?int $userId = null, array $receivedQuantities = []): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $userId, $receivedQuantities) {
            $lockedTransfer = StockTransfer::where('id', $transfer->id)->lockForUpdate()->first();

            if (! $lockedTransfer->isInTransit()) {
                throw new DomainException('Only IN_TRANSIT transfers can be received.', 409);
            }

            $lockedTransfer->load('items');

            // Authorization: User must have access to destination location
            if ($userId) {
                $user = User::find($userId);
                if ($user && ! in_array($lockedTransfer->destination_location_id, $user->getAllowedLocationIds())) {
                    throw new DomainException('User is not authorized for the destination location.', 403);
                }
            }

            // Check if destination location is active
            $destination = Location::find($lockedTransfer->destination_location_id);
            if (! $destination || ! $destination->is_active) {
                throw new DomainException('The destination location is no longer active.', 422);
            }

            // Product non-active allowed during Receive (DECISIONS.md #12)
            // No product active check here.

            $movementType = ($lockedTransfer->transfer_type?->isReturn() ?? false)
                ? MovementType::RETURN_TO_WAREHOUSE
                : MovementType::TRANSFER_IN;

            $receivedMap = [];
            foreach ($receivedQuantities as $itemId => $quantity) {
                $receivedMap[(int) $itemId] = (string) $quantity;
            }

            $dtos = [];
            $hasDiscrepancy = false;

            foreach ($lockedTransfer->items as $item) {
                $sent = (string) $item->quantity;
                $received = array_key_exists($item->id, $receivedMap) ? $receivedMap[$item->id] : $sent;

                if (bccomp($received, '0', 4) < 0) {
                    throw new DomainException('Jumlah diterima tidak boleh negatif.', 422);
                }

                if (bccomp($received, $sent, 4) !== 0) {
                    $hasDiscrepancy = true;
                }

                $item->update(['received_quantity' => $received]);

                if (bccomp($received, '0', 4) > 0) {
                    $dtos[] = new StockChangeDTO(
                        productId: $item->product_id,
                        locationId: $lockedTransfer->destination_location_id,
                        quantity: $received,
                        movementType: $movementType,
                        referenceType: 'App\\Features\\Inventory\\Models\\StockTransfer',
                        referenceId: $lockedTransfer->id,
                        referenceNumber: $lockedTransfer->transfer_number,
                        userId: $userId ?? $lockedTransfer->created_by,
                        occurredAt: now(),
                        condition: $item->condition?->value ?? 'GOOD'
                    );
                }
            }

            if (! empty($dtos)) {
                $this->stockMovementService->recordMultipleMovements($dtos);
            }

            $lockedTransfer->update([
                'status' => $hasDiscrepancy ? TransferStatus::DISCREPANCY : TransferStatus::RECEIVED,
                'received_by' => $userId,
                'received_at' => now(),
            ]);

            return $lockedTransfer;
        }, 5);
    }
}
