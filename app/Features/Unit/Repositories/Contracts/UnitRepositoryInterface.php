<?php

namespace App\Features\Unit\Repositories\Contracts;

use App\Features\Unit\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UnitRepositoryInterface
{
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Unit;

    public function create(array $data): Unit;

    public function update(Unit $unit, array $data): Unit;
}
