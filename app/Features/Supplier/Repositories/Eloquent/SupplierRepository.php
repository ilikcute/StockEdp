<?php

namespace App\Features\Supplier\Repositories\Eloquent;

use App\Features\Supplier\Models\Supplier;
use App\Features\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SupplierRepository implements SupplierRepositoryInterface
{
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Supplier::query()->with(['createdBy', 'updatedBy']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $allowedSorts = ['id', 'code', 'name', 'contact_person', 'is_active', 'created_at'];
        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSorts, true)
            ? $filters['sort_by']
            : 'created_at';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    public function getAllActive(): Collection
    {
        return Supplier::query()
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();
    }

    public function findById(int $id): ?Supplier
    {
        return Supplier::find($id);
    }

    public function create(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier->fresh(['createdBy', 'updatedBy']);
    }
}
