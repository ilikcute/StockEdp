<?php

namespace App\Features\Reporting\Repositories\Eloquent;

use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Product\Models\Product;
use App\Features\Reporting\Repositories\Contracts\ReportingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use Illuminate\Support\Facades\DB;

class ReportingRepository implements ReportingRepositoryInterface
{
    public function getPaginatedBalances(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'id',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        if (empty($allowedLocationIds)) {
            return new ConcretePaginator([], 0, $perPage, 1);
        }

        $query = InventoryBalance::with(['product.category', 'product.unit', 'location.operationLock']);

        // Scope to allowed locations
        $query->whereIn('location_id', $allowedLocationIds);

        // Filter location_id if requested (must be within allowed locations)
        if (! empty($filters['location_id'])) {
            if (! in_array((int) $filters['location_id'], $allowedLocationIds, true)) {
                return new ConcretePaginator([], 0, $perPage, 1);
            }
            $query->where('location_id', $filters['location_id']);
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        if (! empty($filters['unit_id'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('unit_id', $filters['unit_id']);
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $isActive = (bool) $filters['is_active'];
            $query->whereHas('product', function ($q) use ($isActive) {
                $q->where('is_active', $isActive);
            });
        }

        if (! empty($filters['positive_stock'])) {
            $query->where('quantity', '>', '0.0000');
        }

        if (isset($filters['zero_stock']) && $filters['zero_stock'] === '1') {
            $query->where('quantity', '=', '0.0000');
        }

        if (isset($filters['frozen_location']) && $filters['frozen_location'] === '1') {
            $query->whereHas('location.operationLock', function ($q) {
                $q->where('is_frozen', true);
            });
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $allowlist = ['id', 'product_id', 'location_id', 'quantity', 'created_at'];
        $sortField = in_array($sortField, $allowlist, true) ? $sortField : 'id';
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $sortDirection)->paginate($perPage);
    }

    public function getPaginatedLowStock(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'shortage_quantity',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        $locationId = isset($filters['location_id']) ? (int) $filters['location_id'] : 0;

        if ($locationId === 0 || ! in_array($locationId, $allowedLocationIds, true)) {
            return new ConcretePaginator([], 0, $perPage, 1);
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
                DB::raw('GREATEST(products.minimum_stock - COALESCE(inventory_balances.quantity, 0.0000), 0.0000) as shortage_quantity'),
            ])
            ->where('products.minimum_stock', '>', 0)
            ->whereRaw('COALESCE(inventory_balances.quantity, 0.0000) < products.minimum_stock');

        // Product active status filter (default only active)
        if (isset($filters['include_inactive']) && $filters['include_inactive'] === '1') {
            // Include inactive products
        } else {
            $query->where('products.is_active', true);
        }

        if (! empty($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }

        if (! empty($filters['unit_id'])) {
            $query->where('products.unit_id', $filters['unit_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('products.barcode', 'like', "%{$search}%");
            });
        }

        $allowlist = ['shortage_quantity', 'minimum_stock', 'on_hand_quantity', 'product_name', 'sku'];
        $sortField = in_array($sortField, $allowlist, true) ? $sortField : 'shortage_quantity';
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $sortDirection)->paginate($perPage);
    }

    public function getOpeningBalanceForStockCard(
        int $productId,
        int $locationId,
        string $startDateTime
    ): string {
        $movement = StockMovement::where('product_id', $productId)
            ->where('location_id', $locationId)
            ->where('occurred_at', '<', $startDateTime)
            ->orderBy('occurred_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $movement ? (string) $movement->quantity_after : '0.0000';
    }

    public function getPaginatedStockCardMovements(
        int $productId,
        int $locationId,
        string $startDateTime,
        string $endNextDayDateTime,
        int $perPage = 15
    ): LengthAwarePaginator {
        return StockMovement::with(['product.unit', 'location', 'creator'])
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->where('occurred_at', '>=', $startDateTime)
            ->where('occurred_at', '<', $endNextDayDateTime)
            ->orderBy('occurred_at', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($perPage);
    }

    public function getStockCardSummary(
        int $productId,
        int $locationId,
        string $startDateTime,
        string $endNextDayDateTime,
        string $openingBalance
    ): array {
        $movements = StockMovement::where('product_id', $productId)
            ->where('location_id', $locationId)
            ->where('occurred_at', '>=', $startDateTime)
            ->where('occurred_at', '<', $endNextDayDateTime)
            ->orderBy('occurred_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $totalIn = '0.0000';
        $totalOut = '0.0000';

        foreach ($movements as $m) {
            $delta = bcsub((string) $m->quantity_after, (string) $m->quantity_before, 4);
            if (bccomp($delta, '0.0000', 4) > 0) {
                $totalIn = bcadd($totalIn, $delta, 4);
            } elseif (bccomp($delta, '0.0000', 4) < 0) {
                $positiveDelta = bcsub('0.0000', $delta, 4);
                $totalOut = bcadd($totalOut, $positiveDelta, 4);
            }
        }

        $closingBalance = $movements->isNotEmpty()
            ? (string) $movements->last()->quantity_after
            : $openingBalance;

        return [
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'total_quantity_in' => $totalIn,
            'total_quantity_out' => $totalOut,
            'movement_count' => $movements->count(),
        ];
    }
}
