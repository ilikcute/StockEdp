<?php

namespace App\Features\Department\Repositories\Contracts;

use App\Features\Department\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DepartmentRepositoryInterface
{
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getActiveList(): Collection;

    public function findById(int $id): ?Department;

    public function findByCode(string $code): ?Department;

    public function create(array $data): Department;

    public function update(Department $department, array $data): Department;
}
