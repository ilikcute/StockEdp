<?php

namespace App\Features\Replenishment\Repositories\Eloquent;

use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Location\Models\Location;
use App\Features\Replenishment\DTOs\ReplenishmentFilterData;
use App\Features\Replenishment\Enums\ReplenishmentPriority;
use App\Features\Replenishment\Enums\ReplenishmentRecommendationType;
use App\Features\Replenishment\Repositories\Contracts\ReplenishmentRepositoryInterface;
use App\Features\Unit\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
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

    public function getPaginatedLowStock(
        array $allowedLocationIds,
        ReplenishmentFilterData $filters
    ): LengthAwarePaginator {
        $locationId = $filters->locationId;

        if ($locationId === 0 || ! in_array($locationId, $allowedLocationIds, true)) {
            return new ConcretePaginator([], 0, $filters->perPage, 1);
        }

        $query = DB::table('products')
            ->leftJoin('inventory_balances', function ($join) use ($locationId) {
                $join->on('inventory_balances.product_id', '=', 'products.id')
                    ->where('inventory_balances.location_id', '=', $locationId);
            })
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->select([
                'products.id as product_id',
                'products.sku',
                'products.barcode',
                'products.name as product_name',
                'products.minimum_stock',
                'products.is_active as is_product_active',
                'categories.name as category_name',
                'units.name as unit_name',
                DB::raw('COALESCE(inventory_balances.quantity, 0.0000) as on_hand_quantity'),
                DB::raw('GREATEST(products.minimum_stock - COALESCE(inventory_balances.quantity, 0.0000), 0.0000) as gross_shortage_quantity'),
            ])
            ->where('products.minimum_stock', '>', 0)
            ->whereRaw('COALESCE(inventory_balances.quantity, 0.0000) < products.minimum_stock')
            ->where('products.is_active', true);

        if (! empty($filters->categoryId)) {
            $query->where('products.category_id', $filters->categoryId);
        }

        if (! empty($filters->unitId)) {
            $query->where('products.unit_id', $filters->unitId);
        }

        if (! empty($filters->search)) {
            $search = $filters->search;
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('products.barcode', 'like', "%{$search}%");
            });
        }

        if ($filters->priority === ReplenishmentPriority::CRITICAL->value) {
            $query->whereRaw('COALESCE(inventory_balances.quantity, 0.0000) <= 0');
        } elseif ($filters->priority === ReplenishmentPriority::WARNING->value) {
            $query->whereRaw('COALESCE(inventory_balances.quantity, 0.0000) > 0');
        }

        $sortField = $filters->sortBy;
        if ($sortField === 'shortage_quantity') {
            $sortField = 'gross_shortage_quantity';
        }

        $allowlist = [
            'gross_shortage_quantity',
            'minimum_stock',
            'on_hand_quantity',
            'product_name',
            'sku',
        ];

        $sortField = in_array($sortField, $allowlist, true) ? $sortField : 'gross_shortage_quantity';
        $sortDirection = $filters->sortOrder;

        return $query->orderBy($sortField, $sortDirection)
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);
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
            ->whereIn('id', $candidateLocationIds)
            ->where('is_active', true)
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

        $validSourceIds = array_values(array_diff($activeLocationIds, $frozenLocationIds));
        if (empty($validSourceIds)) {
            return [];
        }

        $rows = DB::table('inventory_balances')
            ->join('locations', 'locations.id', '=', 'inventory_balances.location_id')
            ->join('products', 'products.id', '=', 'inventory_balances.product_id')
            ->whereIn('inventory_balances.location_id', $validSourceIds)
            ->whereIn('inventory_balances.product_id', $productIds)
            ->where('locations.is_active', true)
            ->select([
                'inventory_balances.product_id',
                'inventory_balances.location_id',
                'locations.code as location_code',
                'locations.name as location_name',
                'inventory_balances.quantity as source_on_hand_quantity',
                'products.minimum_stock as source_minimum_stock',
            ])
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $pId = (int) $row->product_id;
            if (! isset($grouped[$pId])) {
                $grouped[$pId] = [];
            }
            $grouped[$pId][] = $row;
        }

        return $grouped;
    }

    public function calculateSummaryCounts(
        int $targetLocationId,
        array $allowedLocationIds,
        ReplenishmentFilterData $filters
    ): array {
        if ($targetLocationId === 0 || ! in_array($targetLocationId, $allowedLocationIds, true)) {
            return [
                'low_stock_product_count' => 0,
                'inbound_covered_count' => 0,
                'internal_transfer_count' => 0,
                'mixed_count' => 0,
                'external_reorder_count' => 0,
                'critical_product_count' => 0,
            ];
        }

        $query = DB::table('products')
            ->leftJoin('inventory_balances', function ($join) use ($targetLocationId) {
                $join->on('inventory_balances.product_id', '=', 'products.id')
                    ->where('inventory_balances.location_id', '=', $targetLocationId);
            })
            ->select([
                'products.id as product_id',
                'products.minimum_stock',
                DB::raw('COALESCE(inventory_balances.quantity, 0.0000) as on_hand_quantity'),
                DB::raw('GREATEST(products.minimum_stock - COALESCE(inventory_balances.quantity, 0.0000), 0.0000) as gross_shortage_quantity'),
            ])
            ->where('products.minimum_stock', '>', 0)
            ->whereRaw('COALESCE(inventory_balances.quantity, 0.0000) < products.minimum_stock')
            ->where('products.is_active', true);

        if (! empty($filters->categoryId)) {
            $query->where('products.category_id', $filters->categoryId);
        }

        if (! empty($filters->unitId)) {
            $query->where('products.unit_id', $filters->unitId);
        }

        if (! empty($filters->search)) {
            $search = $filters->search;
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('products.barcode', 'like', "%{$search}%");
            });
        }

        $allLowStockItems = $query->get();
        $totalCount = $allLowStockItems->count();

        if ($totalCount === 0) {
            return [
                'low_stock_product_count' => 0,
                'inbound_covered_count' => 0,
                'internal_transfer_count' => 0,
                'mixed_count' => 0,
                'external_reorder_count' => 0,
                'critical_product_count' => 0,
            ];
        }

        $productIds = $allLowStockItems->pluck('product_id')->map(fn ($id) => (int) $id)->all();
        $pendingInbounds = $this->getPendingInboundQuantities($targetLocationId, $productIds);
        $candidateBalances = $this->getCandidateSourceBalances($targetLocationId, $allowedLocationIds, $productIds);

        $criticalCount = 0;
        $inboundCoveredCount = 0;
        $internalTransferCount = 0;
        $mixedCount = 0;
        $externalReorderCount = 0;

        foreach ($allLowStockItems as $item) {
            $pId = (int) $item->product_id;
            $onHand = (string) $item->on_hand_quantity;
            $minStock = (string) $item->minimum_stock;
            $grossShortage = (string) $item->gross_shortage_quantity;

            if (bccomp($onHand, '0.0000', 4) <= 0) {
                $criticalCount++;
            }

            $pendingInbound = $pendingInbounds[$pId] ?? '0.0000';

            if (bccomp($pendingInbound, $grossShortage, 4) >= 0) {
                $inboundCoveredCount++;

                continue;
            }

            $netNeed = bcsub($grossShortage, $pendingInbound, 4);

            $sourceRows = $candidateBalances[$pId] ?? [];
            $totalSurplus = '0.0000';
            foreach ($sourceRows as $src) {
                $srcOnHand = (string) $src->source_on_hand_quantity;
                $srcMin = (string) $src->source_minimum_stock;
                if (bccomp($srcOnHand, $srcMin, 4) > 0) {
                    $surplus = bcsub($srcOnHand, $srcMin, 4);
                    $totalSurplus = bcadd($totalSurplus, $surplus, 4);
                }
            }

            if (bccomp($totalSurplus, '0.0000', 4) === 0) {
                $externalReorderCount++;
            } elseif (bccomp($totalSurplus, $netNeed, 4) >= 0) {
                $internalTransferCount++;
            } else {
                $mixedCount++;
            }
        }

        return [
            'low_stock_product_count' => $totalCount,
            'inbound_covered_count' => $inboundCoveredCount,
            'internal_transfer_count' => $internalTransferCount,
            'mixed_count' => $mixedCount,
            'external_reorder_count' => $externalReorderCount,
            'critical_product_count' => $criticalCount,
        ];
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
