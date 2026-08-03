<?php

namespace App\Features\Inventory\Actions;

use App\Features\Auth\Models\User;
use App\Features\Inventory\DTOs\StockChangeDTO;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Enums\ReceiptStatus;
use App\Features\Inventory\Models\StockReceipt;
use App\Features\Inventory\Services\InventoryFreezeService;
use App\Features\Inventory\Services\StockMovementService;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Supplier\Models\Supplier;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class PostStockReceiptAction
{
    public function __construct(
        private readonly StockMovementService $stockMovementService
    ) {}

    public function execute(StockReceipt $receipt, ?int $userId = null): StockReceipt
    {
        return DB::transaction(function () use ($receipt, $userId) {
            // Pre-fetch items location IDs to enforce global lock order (Lock Location Locks FIRST)
            $rawLocationIds = DB::table('stock_receipt_items')
                ->where('stock_receipt_id', $receipt->id)
                ->pluck('location_id')
                ->toArray();

            if (! empty($rawLocationIds)) {
                app(InventoryFreezeService::class)->lockAndValidateLocations($rawLocationIds);
            }

            $lockedReceipt = StockReceipt::where('id', $receipt->id)->lockForUpdate()->first();

            if ($lockedReceipt->isPosted()) {
                throw new DomainException('Receipt is already POSTED.', 409);
            }

            if (! $lockedReceipt->isDraft()) {
                throw new DomainException('Only DRAFT receipts can be posted.', 409);
            }

            $lockedReceipt->load('items');
            if ($lockedReceipt->items->isEmpty()) {
                throw new DomainException('Cannot post an empty receipt.', 422);
            }

            // Check authorization
            $locationIds = $lockedReceipt->items->pluck('location_id')->unique()->toArray();
            if ($userId) {
                $user = User::find($userId);
                if ($user && count(array_diff($locationIds, $user->getAllowedLocationIds())) > 0) {
                    throw new DomainException('User is not authorized for one or more locations in this document.', 403);
                }
            }

            // Check if all products, locations, and supplier are active
            $productIds = $lockedReceipt->items->pluck('product_id')->unique()->toArray();
            $activeProducts = Product::whereIn('id', $productIds)->where('is_active', true)->count();
            if ($activeProducts !== count($productIds)) {
                throw new DomainException('One or more products in this document are no longer active.', 422);
            }

            $activeLocations = Location::whereIn('id', $locationIds)->where('is_active', true)->count();
            if ($activeLocations !== count($locationIds)) {
                throw new DomainException('One or more locations in this document are no longer active.', 422);
            }

            $supplier = Supplier::find($lockedReceipt->supplier_id);
            if (! $supplier || ! $supplier->is_active) {
                throw new DomainException('The supplier is no longer active.', 422);
            }

            $dtos = [];
            foreach ($lockedReceipt->items as $item) {
                $dtos[] = new StockChangeDTO(
                    productId: $item->product_id,
                    locationId: $item->location_id,
                    quantity: (string) $item->quantity,
                    movementType: MovementType::RECEIPT,
                    referenceType: StockReceipt::class,
                    referenceId: $lockedReceipt->id,
                    referenceNumber: $lockedReceipt->receipt_number,
                    userId: $userId ?? $lockedReceipt->created_by,
                    occurredAt: clone $lockedReceipt->date
                );
            }

            $this->stockMovementService->recordMultipleMovements($dtos);

            $lockedReceipt->update([
                'status' => ReceiptStatus::POSTED,
            ]);

            return $lockedReceipt;
        }, 5);
    }
}
