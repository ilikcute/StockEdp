<?php

namespace App\Features\Inventory\Actions;

use App\Features\Auth\Models\User;
use App\Features\Inventory\DTOs\StockChangeDTO;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Inventory\Services\StockMovementService;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class SendStockTransferAction
{
    public function __construct(
        private readonly StockMovementService $stockMovementService
    ) {}

    public function execute(StockTransfer $transfer, ?int $userId = null): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $userId) {
            $lockedTransfer = StockTransfer::where('id', $transfer->id)->lockForUpdate()->first();

            if (! $lockedTransfer->isDraft()) {
                throw new DomainException('Only DRAFT transfers can be sent.', 409);
            }

            $lockedTransfer->load('items');
            if ($lockedTransfer->items->isEmpty()) {
                throw new DomainException('Cannot send an empty transfer.', 422);
            }

            // Authorization: User must have access to origin location
            if ($userId) {
                $user = User::find($userId);
                if ($user && ! in_array($lockedTransfer->origin_location_id, $user->getAllowedLocationIds())) {
                    throw new DomainException('User is not authorized for the origin location.', 403);
                }
            }

            // Check if origin and destination locations are active
            $locations = Location::whereIn('id', [$lockedTransfer->origin_location_id, $lockedTransfer->destination_location_id])
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();

            if (! in_array($lockedTransfer->origin_location_id, $locations)) {
                throw new DomainException('The origin location is no longer active.', 422);
            }
            if (! in_array($lockedTransfer->destination_location_id, $locations)) {
                throw new DomainException('The destination location is no longer active.', 422);
            }

            // Check if all products are active
            $productIds = $lockedTransfer->items->pluck('product_id')->unique()->toArray();
            $activeProductsCount = Product::whereIn('id', $productIds)->where('is_active', true)->count();
            if ($activeProductsCount !== count($productIds)) {
                throw new DomainException('One or more products in this transfer are no longer active.', 422);
            }

            $dtos = [];
            foreach ($lockedTransfer->items as $item) {
                $dtos[] = new StockChangeDTO(
                    productId: $item->product_id,
                    locationId: $lockedTransfer->origin_location_id,
                    quantity: (string) $item->quantity,
                    movementType: MovementType::TRANSFER_OUT,
                    referenceType: 'App\\Features\\Inventory\\Models\\StockTransfer',
                    referenceId: $lockedTransfer->id,
                    referenceNumber: $lockedTransfer->transfer_number,
                    userId: $userId ?? $lockedTransfer->created_by,
                    occurredAt: now()
                );
            }

            $this->stockMovementService->recordMultipleMovements($dtos);

            $lockedTransfer->update([
                'status' => TransferStatus::SENT,
                'sent_by' => $userId,
                'sent_at' => now(),
            ]);

            return $lockedTransfer;
        });
    }
}
