<?php

namespace App\Features\Inventory\Repositories\Eloquent;

use App\Features\Inventory\Models\StockMovement;
use App\Features\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;

class StockMovementRepository implements StockMovementRepositoryInterface
{
    public function create(array $data): StockMovement
    {
        return StockMovement::create($data);
    }

    public function getPaginatedMovements(array $filters, string $sortField = 'created_at', string $sortDirection = 'desc', int $perPage = 15)
    {
        $query = StockMovement::with(['product', 'location', 'creator']);

        $allowedLocations = auth()->user() ? auth()->user()->getAllowedLocationIds() : [];
        $query->whereIn('location_id', $allowedLocations);

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (! empty($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('occurred_at', [$filters['start_date'], $filters['end_date']]);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy($sortField, $sortDirection)->paginate($perPage);
    }
}
