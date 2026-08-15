<?php

namespace App\Features\Reporting\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class LowStockQuery
{
    /**
     * Create the single canonical low stock query for a given location.
     *
     * Invariants:
     * - products.is_active = true (unless include_inactive = '1')
     * - minimum_stock > 0
     * - on_hand = COALESCE(inventory_balances.quantity, 0.0000)
     * - on_hand < minimum_stock
     * - shortage = GREATEST(products.minimum_stock - COALESCE(inventory_balances.quantity, 0.0000), 0.0000)
     *
     * @param  array  $filters  [include_inactive, category_id, unit_id, search, priority, include_location_info]
     */
    public static function forLocation(int $locationId, array $filters = []): Builder
    {
        $query = DB::table('products')
            ->leftJoin('inventory_balances', function ($join) use ($locationId) {
                $join->on('inventory_balances.product_id', '=', 'products.id')
                    ->where('inventory_balances.location_id', '=', $locationId);
            })
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id');

        $selects = [
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
            DB::raw('GREATEST(products.minimum_stock - COALESCE(inventory_balances.quantity, 0.0000), 0.0000) as gross_shortage_quantity'),
        ];

        if (! empty($filters['include_location_info'])) {
            $query->leftJoin('locations', 'locations.id', '=', DB::raw($locationId));
            $selects[] = 'locations.code as location_code';
            $selects[] = 'locations.name as location_name';
        }

        $query->select($selects)
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

        if (isset($filters['priority'])) {
            if ($filters['priority'] === 'CRITICAL') {
                $query->whereRaw('COALESCE(inventory_balances.quantity, 0.0000) <= 0');
            } elseif ($filters['priority'] === 'WARNING') {
                $query->whereRaw('COALESCE(inventory_balances.quantity, 0.0000) > 0');
            }
        }

        return $query;
    }
}
