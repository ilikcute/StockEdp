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

    public function execute(StockTransfer $transfer, ?int $userId = null): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $userId) {
            $lockedTransfer = StockTransfer::where('id', $transfer->id)->lockForUpdate()->first();

            if (! $lockedTransfer->isSent()) {
                throw new DomainException('Only SENT transfers can be received.', 409);
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

            $dtos = [];
            foreach ($lockedTransfer->items as $item) {
                $dtos[] = new StockChangeDTO(
                    productId: $item->product_id,
                    locationId: $lockedTransfer->destination_location_id,
                    quantity: (string) $item->quantity,
                    movementType: MovementType::TRANSFER_IN,
                    referenceType: 'App\\Features\\Inventory\\Models\\StockTransfer',
                    referenceId: $lockedTransfer->id,
                    referenceNumber: $lockedTransfer->transfer_number,
                    userId: $userId ?? $lockedTransfer->created_by,
                    occurredAt: now()
                );
            }

            $this->stockMovementService->recordMultipleMovements($dtos);

            $lockedTransfer->update([
                'status' => TransferStatus::RECEIVED,
                'received_by' => $userId,
                'received_at' => now(),
            ]);

            return $lockedTransfer;
        });
    }
}
