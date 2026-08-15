<?php

namespace App\Features\Replenishment\Repositories\Eloquent;

use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Location\Models\Location;
use App\Features\Replenishment\DTOs\ReplenishmentFilterData;
use App\Features\Replenishment\Enums\ReplenishmentPriority;
use App\Features\Replenishment\Enums\ReplenishmentRecommendationType;
use App\Features\Replenishment\Repositories\Contracts\ReplenishmentRepositoryInterface;
use App\Features\Reporting\Queries\LowStockQuery;
use App\Features\Unit\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReplenishmentRepository implements ReplenishmentRepositoryInterface
{
    public function getTargetLocation(int $locationId): ?Location
    {
        return Location::find($locationId);
    }

    public function isLocationFrozen(int $locationId): bool
    {
        return (bool) DB::table('inventory_location_locks')
            ->where('location_id', $locationId)
            ->value('is_frozen');
    }

    public function getLowStockCandidates(
        array $allowedLocationIds,
        ReplenishmentFilterData $filters
    ): Collection {
        $locationId = $filters->locationId;

        if ($locationId === 0 || ! in_array($locationId, $allowedLocationIds, true)) {
            return collect();
        }

        $queryFilters = [
            'category_id' => $filters->categoryId,
            'unit_id' => $filters->unitId,
            'search' => $filters->search,
            'priority' => $filters->priority,
        ];

        return LowStockQuery::forLocation($locationId, $queryFilters)->get();
    }

    public function getPendingInboundQuantities(
        int $targetLocationId,
        array $productIds
    ): array {
        if (empty($productIds)) {
            return [];
        }

        $results = DB::table('stock_transfer_items')
            ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
            ->where('stock_transfers.status', TransferStatus::SENT->value)
            ->where('stock_transfers.destination_location_id', $targetLocationId)
            ->whereIn('stock_transfer_items.product_id', $productIds)
            ->groupBy('stock_transfer_items.product_id')
            ->select([
                'stock_transfer_items.product_id',
                DB::raw('COALESCE(SUM(stock_transfer_items.quantity), 0.0000) as pending_qty'),
            ])
            ->pluck('pending_qty', 'product_id')
            ->all();

        $formatted = [];
        foreach ($results as $productId => $qty) {
            $formatted[(int) $productId] = (string) $qty;
        }

        return $formatted;
    }

    public function getCandidateSourceBalances(
        int $targetLocationId,
        array $allowedLocationIds,
        array $productIds
    ): array {
        if (empty($productIds)) {
            return [];
        }

        $candidateLocationIds = array_values(array_diff($allowedLocationIds, [$targetLocationId]));
        if (empty($candidateLocationIds)) {
            return [];
        }

        $activeLocationIds = DB::table('locations')
            ->where('is_active', true)
            ->whereIn('id', $candidateLocationIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($activeLocationIds)) {
            return [];
        }

        $frozenLocationIds = DB::table('inventory_location_locks')
            ->whereIn('location_id', $activeLocationIds)
            ->where('is_frozen', true)
            ->pluck('location_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $safeLocationIds = array_values(array_diff($activeLocationIds, $frozenLocationIds));
        if (empty($safeLocationIds)) {
            return [];
        }

        $rows = DB::table('products')
            ->join('inventory_balances', function ($join) use ($safeLocationIds) {
                $join->on('inventory_balances.product_id', '=', 'products.id')
                    ->whereIn('inventory_balances.location_id', $safeLocationIds);
            })
            ->join('locations', 'locations.id', '=', 'inventory_balances.location_id')
            ->whereIn('products.id', $productIds)
            ->select([
                'products.id as product_id',
                'products.minimum_stock as source_minimum_stock',
                'locations.id as location_id',
                'locations.code as location_code',
                'locations.name as location_name',
                DB::raw('COALESCE(inventory_balances.quantity, 0.0000) as source_on_hand_quantity'),
            ])
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row->product_id][] = $row;
        }

        return $grouped;
    }

    public function getFilterOptions(array $allowedLocationIds): array
    {
        $locations = Location::where('is_active', true)
            ->whereIn('id', $allowedLocationIds)
            ->orderBy('name', 'asc')
            ->get(['id', 'code', 'name']);

        $categories = Category::where('is_active', true)
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        $units = Unit::where('is_active', true)
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        $recommendationTypes = array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], ReplenishmentRecommendationType::cases());

        $priorities = array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], ReplenishmentPriority::cases());

        return [
            'locations' => $locations,
            'categories' => $categories,
            'units' => $units,
            'recommendation_types' => $recommendationTypes,
            'priorities' => $priorities,
        ];
    }
}
