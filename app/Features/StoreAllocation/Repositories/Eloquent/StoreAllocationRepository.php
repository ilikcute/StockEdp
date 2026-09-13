<?php

namespace App\Features\StoreAllocation\Repositories\Eloquent;

use App\Features\StoreAllocation\Models\StoreAllocation;
use App\Features\StoreAllocation\Repositories\Contracts\StoreAllocationRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StoreAllocationRepository implements StoreAllocationRepositoryInterface
{
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = StoreAllocation::with([
            'technician',
            'location',
            'store',
            'creator',
            'items.product',
            'items.pulledProduct',
        ]);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('allocation_number', 'like', "%{$search}%")
                    ->orWhereHas('store', fn ($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('technician', fn ($tq) => $tq->where('name', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        if (! empty($filters['technician_user_id'])) {
            $query->where('technician_user_id', $filters['technician_user_id']);
        }

        if (! empty($filters['technician_location_id'])) {
            $query->where('technician_location_id', $filters['technician_location_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('allocated_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('allocated_at', '<=', $filters['date_to']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'allocation_number', 'allocated_at', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?StoreAllocation
    {
        return StoreAllocation::with([
            'technician',
            'location',
            'store',
            'creator',
            'items.product',
            'items.pulledProduct',
        ])->find($id);
    }

    public function findByNumber(string $number): ?StoreAllocation
    {
        return StoreAllocation::with([
            'technician',
            'location',
            'store',
            'creator',
            'items.product',
            'items.pulledProduct',
        ])->where('allocation_number', $number)->first();
    }

    public function getNextAllocationNumber(): string
    {
        $today = Carbon::today()->format('Ymd');
        $prefix = "ALC-{$today}-";

        $fetchNext = function () use ($prefix) {
            $lastRecord = StoreAllocation::where('allocation_number', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            if ($lastRecord) {
                $lastNumber = (int) substr($lastRecord->allocation_number, strlen($prefix));
                $nextNumber = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
            } else {
                $nextNumber = '0001';
            }

            return $prefix.$nextNumber;
        };

        if (DB::transactionLevel() > 0) {
            return $fetchNext();
        }

        return DB::transaction($fetchNext);
    }
}
