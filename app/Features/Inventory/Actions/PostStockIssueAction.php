<?php

namespace App\Features\Inventory\Actions;

use App\Features\Auth\Models\User;
use App\Features\Inventory\DTOs\StockChangeDTO;
use App\Features\Inventory\Enums\IssueStatus;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Services\StockMovementService;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class PostStockIssueAction
{
    public function __construct(
        private readonly StockMovementService $movementService
    ) {}

    public function execute(StockIssue $issue, int $userId): StockIssue
    {
        return DB::transaction(function () use ($issue, $userId) {
            $rawLocationIds = DB::table('stock_issue_items')
                ->where('stock_issue_id', $issue->id)
                ->pluck('location_id')
                ->toArray();
            if (! empty($rawLocationIds)) {
                app(\App\Features\Inventory\Services\InventoryFreezeService::class)->lockAndValidateLocations($rawLocationIds);
            }

            // Lock document to prevent concurrent posting
            $lockedIssue = StockIssue::where('id', $issue->id)->lockForUpdate()->first();

            if (! $lockedIssue->isDraft()) {
                throw new DomainException('Only DRAFT issues can be posted.', 409);
            }

            // Check authorization
            $locationIds = $lockedIssue->items->pluck('location_id')->unique()->toArray();
            $user = User::find($userId);
            if ($user && count(array_diff($locationIds, $user->getAllowedLocationIds())) > 0) {
                throw new DomainException('User is not authorized for one or more locations in this document.', 403);
            }

            // Check if all products and locations are active
            $productIds = $lockedIssue->items->pluck('product_id')->unique()->toArray();
            $activeProducts = Product::whereIn('id', $productIds)->where('is_active', true)->count();
            if ($activeProducts !== count($productIds)) {
                throw new DomainException('One or more products in this document are no longer active.', 422);
            }

            $activeLocations = Location::whereIn('id', $locationIds)->where('is_active', true)->count();
            if ($activeLocations !== count($locationIds)) {
                throw new DomainException('One or more locations in this document are no longer active.', 422);
            }

            $lockedIssue->status = IssueStatus::POSTED;
            $lockedIssue->save();

            $dtos = [];
            foreach ($lockedIssue->items as $item) {
                $dtos[] = new StockChangeDTO(
                    productId: $item->product_id,
                    locationId: $item->location_id,
                    quantity: $item->quantity,
                    movementType: MovementType::ISSUE,
                    referenceType: StockIssue::class,
                    referenceId: $lockedIssue->id,
                    referenceNumber: $lockedIssue->issue_number,
                    userId: $userId,
                    occurredAt: $lockedIssue->date
                );
            }

            $this->movementService->recordMultipleMovements($dtos);

            return $lockedIssue;
        }, 5);
    }
}
