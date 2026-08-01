<?php

namespace App\Features\Unit\Repositories\Contracts;

use App\Features\Unit\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UnitRepositoryInterface
{
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getAllActive(): Collection;

    public function findById(int $id): ?Unit;

    public function create(array $data): Unit;

    public function update(Unit $unit, array $data): Unit;
}
