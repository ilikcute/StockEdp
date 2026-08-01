<?php

namespace App\Features\Inventory\Repositories\Eloquent;

use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Repositories\Contracts\StockIssueRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class StockIssueRepository implements StockIssueRepositoryInterface
{
    public function getPaginatedIssues(array $filters, string $sortField, string $sortDirection, int $perPage): LengthAwarePaginator
    {
        $query = StockIssue::with(['creator']);

        $allowedLocations = auth()->user() ? auth()->user()->getAllowedLocationIds() : [];
        $query->whereDoesntHave('items', function ($q) use ($allowedLocations) {
            $q->whereNotIn('location_id', $allowedLocations);
        })->whereHas('items');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
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
                    ->orWhere('notes', 'like', "%{$search}%");
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
        return StockIssue::with(['items.product', 'items.location', 'creator'])->find($id);
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

        $lastIssue = StockIssue::where('issue_number', 'like', $prefix.'%')
            ->orderBy('issue_number', 'desc')
            ->first();

        if (! $lastIssue) {
            return $prefix.'0001';
        }

        $lastNumber = (int) substr($lastIssue->issue_number, -4);
        $nextNumber = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);

        return $prefix.$nextNumber;
    }
}
