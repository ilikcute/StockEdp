<?php

namespace App\Features\Inventory\Repositories\Eloquent;

use App\Features\Inventory\Models\StockReceipt;
use App\Features\Inventory\Repositories\Contracts\StockReceiptRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class StockReceiptRepository implements StockReceiptRepositoryInterface
{
    public function getPaginatedReceipts(array $filters, string $sortField, string $sortDirection, int $perPage): LengthAwarePaginator
    {
        $query = StockReceipt::with(['supplier', 'creator']);

        $allowedLocations = auth()->user() ? auth()->user()->getAllowedLocationIds() : [];
        $query->whereDoesntHave('items', function ($q) use ($allowedLocations) {
            $q->whereNotIn('location_id', $allowedLocations);
        })->whereHas('items');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('date', '<=', $filters['end_date']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $allowedSorts = ['id', 'receipt_number', 'date', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?StockReceipt
    {
        return StockReceipt::with(['items.product', 'items.location', 'supplier', 'creator'])->find($id);
    }

    public function create(array $data): StockReceipt
    {
        return StockReceipt::create($data);
    }

    public function update(StockReceipt $receipt, array $data): bool
    {
        return $receipt->update($data);
    }

    public function generateReceiptNumber(): string
    {
        $prefix = 'REC-'.now()->format('Ym').'-';

        $lastReceipt = StockReceipt::where('receipt_number', 'like', $prefix.'%')
            ->orderBy('receipt_number', 'desc')
            ->first();

        if (! $lastReceipt) {
            return $prefix.'0001';
        }

        $lastNumber = (int) substr($lastReceipt->receipt_number, -4);
        $nextNumber = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);

        return $prefix.$nextNumber;
    }
}
