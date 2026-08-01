<?php

namespace App\Features\Inventory\Repositories\Eloquent;

use App\Features\Inventory\Models\StockAdjustment;
use App\Features\Inventory\Repositories\Contracts\StockAdjustmentRepositoryInterface;
use App\Shared\Exceptions\DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StockAdjustmentRepository implements StockAdjustmentRepositoryInterface
{
    public function getPaginatedAdjustments(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'id',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = StockAdjustment::with(['location', 'creator', 'poster']);

        // Scope by allowed locations. If empty, return empty paginator scope
        if (empty($allowedLocationIds)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('location_id', $allowedLocationIds);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (! empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }

        if (! empty($filters['reason_code'])) {
            $query->where('reason_code', $filters['reason_code']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('adjustment_date', [$filters['start_date'], $filters['end_date']]);
        }

        $allowedSorts = ['id', 'adjustment_number', 'adjustment_date', 'created_at'];
        if (! in_array($sortField, $allowedSorts, true)) {
            $sortField = 'id';
        }

        return $query->orderBy($sortField, $sortDirection)->paginate($perPage);
    }

    public function findById(int $id): ?StockAdjustment
    {
        return StockAdjustment::with(['items.product', 'location', 'creator', 'updater', 'poster', 'canceler'])
            ->find($id);
    }

    public function create(array $data): StockAdjustment
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $adjustment = StockAdjustment::create($data);

            if (! empty($items)) {
                $adjustment->items()->createMany($items);
            }

            return $adjustment->load('items');
        });
    }

    public function update(StockAdjustment $adjustment, array $data): StockAdjustment
    {
        return DB::transaction(function () use ($adjustment, $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $adjustment->update($data);

            if (! empty($items)) {
                $adjustment->items()->delete();
                $adjustment->items()->createMany($items);
            }

            return $adjustment->load('items');
        });
    }

    public function generateAdjustmentNumber(): string
    {
        $prefix = 'ADJ-'.now()->format('Ym').'-';

        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            DB::beginTransaction();
            try {
                $latest = StockAdjustment::where('adjustment_number', 'like', $prefix.'%')
                    ->lockForUpdate()
                    ->orderBy('id', 'desc')
                    ->first();

                $nextNumber = 1;
                if ($latest) {
                    $lastNumberStr = substr($latest->adjustment_number, -4);
                    $nextNumber = intval($lastNumberStr) + 1;
                }

                if ($nextNumber > 9999) {
                    throw new DomainException('Maximum adjustment number for this month has been reached.', 422);
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
                    if (($e->errorInfo[1] ?? 0) === 1062 && str_contains($e->getMessage(), 'adjustment_number')) {
                        $isDuplicate = true;
                    }
                }

                $attempt++;
                if ($attempt >= $maxRetries) {
                    throw new DomainException('Gagal membuat nomor adjustment karena tingginya transaksi bersamaan. Silakan coba lagi.', 409);
                }

                if (! $isDuplicate && ! str_contains(strtolower($e->getMessage()), 'deadlock')) {
                    throw $e;
                }

                usleep(50000);
            }
        }

        throw new DomainException('Gagal membuat nomor adjustment.', 500);
    }
}
