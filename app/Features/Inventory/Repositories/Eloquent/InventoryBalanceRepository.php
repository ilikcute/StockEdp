<?php

namespace App\Features\Inventory\Repositories\Eloquent;

use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Repositories\Contracts\InventoryBalanceRepositoryInterface;
use Illuminate\Support\Facades\DB;

class InventoryBalanceRepository implements InventoryBalanceRepositoryInterface
{
    public function lockBalanceForUpdate(int $productId, int $locationId): InventoryBalance
    {
        // insertOrIgnore prevents race condition errors on first balance creation.
        // It's atomic in MySQL and safely ignores duplicate key errors.
        DB::table('inventory_balances')->insertOrIgnore([
            'product_id' => $productId,
            'location_id' => $locationId,
            'quantity' => '0.0000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return InventoryBalance::where('product_id', $productId)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->first();
    }

    public function getBalance(int $productId, int $locationId): ?InventoryBalance
    {
        return InventoryBalance::where('product_id', $productId)
            ->where('location_id', $locationId)
            ->first();
    }

    public function getPaginatedBalances(array $filters, string $sortField = 'id', string $sortDirection = 'desc', int $perPage = 15)
    {
        $query = InventoryBalance::with(['product', 'location']);

        $allowedLocations = auth()->user() ? auth()->user()->getAllowedLocationIds() : [];
        $query->whereIn('location_id', $allowedLocations);

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        return $query->orderBy($sortField, $sortDirection)->paginate($perPage);
    }
}
