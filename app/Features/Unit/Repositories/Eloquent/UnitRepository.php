<?php

namespace App\Features\Unit\Repositories\Eloquent;

use App\Features\Unit\Models\Unit;
use App\Features\Unit\Repositories\Contracts\UnitRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UnitRepository implements UnitRepositoryInterface
{
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Unit::query()->with(['createdBy', 'updatedBy']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'code', 'name', 'symbol', 'is_active', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        return $query->paginate($perPage);
    }

    public function getAllActive(): Collection
    {
        return Unit::query()
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();
    }

    public function findById(int $id): ?Unit
    {
        return Unit::with(['createdBy', 'updatedBy'])->find($id);
    }

    public function create(array $data): Unit
    {
        return Unit::create($data);
    }

    public function update(Unit $unit, array $data): Unit
    {
        $unit->update($data);

        return $unit->fresh(['createdBy', 'updatedBy']);
    }
}
