<?php

namespace App\Features\Department\Repositories\Eloquent;

use App\Features\Department\Models\Department;
use App\Features\Department\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Department::with(['createdBy', 'updatedBy']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = strtolower($filters['sort_order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'code', 'name', 'is_active', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }

        return $query->paginate($perPage);
    }

    public function getActiveList(): Collection
    {
        return Department::where('is_active', true)
            ->orderBy('name', 'asc')
            ->get(['id', 'code', 'name']);
    }

    public function findById(int $id): ?Department
    {
        return Department::with(['createdBy', 'updatedBy'])->find($id);
    }

    public function findByCode(string $code): ?Department
    {
        return Department::with(['createdBy', 'updatedBy'])->where('code', strtoupper($code))->first();
    }

    public function create(array $data): Department
    {
        $department = Department::create($data);

        return $department->fresh(['createdBy', 'updatedBy']);
    }

    public function update(Department $department, array $data): Department
    {
        $department->update($data);

        return $department->fresh(['createdBy', 'updatedBy']);
    }
}
