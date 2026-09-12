<?php

namespace App\Features\Store\Repositories\Eloquent;

use App\Features\Store\Models\Store;
use App\Features\Store\Repositories\Contracts\StoreRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StoreRepository implements StoreRepositoryInterface
{
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Store::with(['createdBy', 'updatedBy']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'code', 'name', 'is_active', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Store
    {
        return Store::with(['createdBy', 'updatedBy'])->find($id);
    }

    public function findByCode(string $code): ?Store
    {
        return Store::with(['createdBy', 'updatedBy'])->where('code', strtoupper($code))->first();
    }

    public function create(array $data): Store
    {
        $store = Store::create($data);

        return $store->fresh(['createdBy', 'updatedBy']);
    }

    public function update(Store $store, array $data): Store
    {
        $store->update($data);

        return $store->fresh(['createdBy', 'updatedBy']);
    }

    public function delete(Store $store): bool
    {
        return $store->delete();
    }
}
