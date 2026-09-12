<?php

namespace App\Features\Inventory\Repositories\Eloquent;

use App\Features\Inventory\Models\StockReceipt;
use App\Features\Inventory\Repositories\Contracts\StockReceiptRepositoryInterface;
use App\Shared\Exceptions\DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
        return StockReceipt::with(['items.product.unit', 'items.location', 'supplier', 'creator'])->find($id);
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

        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            DB::beginTransaction();
            try {
                $latest = StockReceipt::where('receipt_number', 'like', $prefix.'%')
                    ->lockForUpdate()
                    ->orderBy('id', 'desc')
                    ->first();

                $nextNumber = 1;
                if ($latest) {
                    $lastNumberStr = substr($latest->receipt_number, -4);
                    $nextNumber = intval($lastNumberStr) + 1;
                }

                if ($nextNumber > 9999) {
                    throw new DomainException('Maximum receipt number for this month has been reached.', 422);
                }

                $newNumber = $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);

                DB::commit();

                return $newNumber;
            } catch (\Exception $e) {
                DB::rollBack();
                if ($e instanceof DomainException) {
                    throw $e;
                }

                $isDuplicate = false;
                if ($e instanceof QueryException) {
                    if (($e->errorInfo[1] ?? 0) === 1062 && str_contains($e->getMessage(), 'receipt_number')) {
                        $isDuplicate = true;
                    }
                }

                $attempt++;
                if ($attempt >= $maxRetries) {
                    throw new DomainException('Gagal membuat nomor penerimaan karena tingginya transaksi bersamaan. Silakan coba lagi.', 409);
                }

                if (! $isDuplicate && ! str_contains(strtolower($e->getMessage()), 'deadlock')) {
                    throw $e;
                }

                usleep(50000);
            }
        }

        throw new DomainException('Gagal membuat nomor penerimaan.', 500);
    }
}
