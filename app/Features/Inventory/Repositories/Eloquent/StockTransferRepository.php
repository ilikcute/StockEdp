<?php

namespace App\Features\Inventory\Repositories\Eloquent;

use App\Features\Inventory\Models\StockTransfer;
use App\Features\Inventory\Repositories\Contracts\StockTransferRepositoryInterface;
use App\Shared\Exceptions\DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StockTransferRepository implements StockTransferRepositoryInterface
{
    public function getPaginatedTransfers(array $filters, string $sortField = 'id', string $sortDirection = 'desc', int $perPage = 15): LengthAwarePaginator
    {
        $query = StockTransfer::with(['originLocation', 'destinationLocation', 'creator']);

        $allowedLocations = auth()->user() ? auth()->user()->getAllowedLocationIds() : [];
        $query->where(function ($q) use ($allowedLocations) {
            $q->whereIn('origin_location_id', $allowedLocations)
                ->orWhereIn('destination_location_id', $allowedLocations);
        });

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('transfer_number', 'like', "%{$search}%");
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('transfer_date', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->orderBy($sortField, $sortDirection)->paginate($perPage);
    }

    public function findById(int $id): ?StockTransfer
    {
        return StockTransfer::with(['items.product', 'originLocation', 'destinationLocation'])
            ->find($id);
    }

    public function create(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $transfer = StockTransfer::create($data);

            if (! empty($items)) {
                $transfer->items()->createMany($items);
            }

            return $transfer->load('items');
        });
    }

    public function update(StockTransfer $transfer, array $data): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $transfer->update($data);

            if (! empty($items)) {
                $transfer->items()->delete();
                $transfer->items()->createMany($items);
            }

            return $transfer->load('items');
        });
    }

    public function generateTransferNumber(): string
    {
        $prefix = 'TRF-'.now()->format('Ym').'-';

        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            DB::beginTransaction();
            try {
                // Lock the latest transfer for the current month
                $latest = StockTransfer::where('transfer_number', 'like', $prefix.'%')
                    ->lockForUpdate()
                    ->orderBy('id', 'desc')
                    ->first();

                $nextNumber = 1;
                if ($latest) {
                    $lastNumberStr = substr($latest->transfer_number, -4);
                    $nextNumber = intval($lastNumberStr) + 1;
                }

                if ($nextNumber > 9999) {
                    throw new DomainException('Maximum transfer number for this month has been reached.', 422);
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
                    // Check if error is MySQL 1062 duplicate entry for transfer_number
                    if (($e->errorInfo[1] ?? 0) === 1062 && str_contains($e->getMessage(), 'transfer_number')) {
                        $isDuplicate = true;
                    }
                }

                $attempt++;
                if ($attempt >= $maxRetries) {
                    throw new DomainException('Gagal membuat nomor transfer karena tingginya transaksi bersamaan. Silakan coba lagi.', 409);
                }

                if (! $isDuplicate && ! str_contains(strtolower($e->getMessage()), 'deadlock')) {
                    throw $e;
                }

                usleep(50000); // 50ms wait
            }
        }

        throw new DomainException('Gagal membuat nomor transfer.', 500);
    }
}
