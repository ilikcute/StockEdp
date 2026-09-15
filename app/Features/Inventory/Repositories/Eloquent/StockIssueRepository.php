<?php

namespace App\Features\Inventory\Repositories\Eloquent;

use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Repositories\Contracts\StockIssueRepositoryInterface;
use App\Shared\Exceptions\DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StockIssueRepository implements StockIssueRepositoryInterface
{
    public function getPaginatedIssues(array $filters, string $sortField, string $sortDirection, int $perPage): LengthAwarePaginator
    {
        $query = StockIssue::with(['creator', 'department']);

        $allowedLocations = auth()->user() ? auth()->user()->getAllowedLocationIds() : [];
        $query->whereDoesntHave('items', function ($q) use ($allowedLocations) {
            $q->whereNotIn('location_id', $allowedLocations);
        })->whereHas('items');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
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
                $q->where('issue_number', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('department', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        $allowedSorts = ['id', 'issue_number', 'date', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?StockIssue
    {
        return StockIssue::with(['items.product.unit', 'items.location', 'creator', 'department'])->find($id);
    }

    public function create(array $data): StockIssue
    {
        return StockIssue::create($data);
    }

    public function update(StockIssue $issue, array $data): bool
    {
        return $issue->update($data);
    }

    public function generateIssueNumber(): string
    {
        $prefix = 'ISS-'.now()->format('Ym').'-';

        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            DB::beginTransaction();
            try {
                $latest = StockIssue::where('issue_number', 'like', $prefix.'%')
                    ->lockForUpdate()
                    ->orderBy('id', 'desc')
                    ->first();

                $nextNumber = 1;
                if ($latest) {
                    $lastNumberStr = substr($latest->issue_number, -4);
                    $nextNumber = intval($lastNumberStr) + 1;
                }

                if ($nextNumber > 9999) {
                    throw new DomainException('Maximum issue number for this month has been reached.', 422);
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
                    if (($e->errorInfo[1] ?? 0) === 1062 && str_contains($e->getMessage(), 'issue_number')) {
                        $isDuplicate = true;
                    }
                }

                $attempt++;
                if ($attempt >= $maxRetries) {
                    throw new DomainException('Gagal membuat nomor pengeluaran karena tingginya transaksi bersamaan. Silakan coba lagi.', 409);
                }

                if (! $isDuplicate && ! str_contains(strtolower($e->getMessage()), 'deadlock')) {
                    throw $e;
                }

                usleep(50000);
            }
        }

        throw new DomainException('Gagal membuat nomor pengeluaran.', 500);
    }
}
