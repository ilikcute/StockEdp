<?php

namespace App\Features\StoreAllocation\Repositories\Contracts;

use App\Features\StoreAllocation\Models\StoreAllocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StoreAllocationRepositoryInterface
{
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?StoreAllocation;

    public function findByNumber(string $number): ?StoreAllocation;

    public function getNextAllocationNumber(): string;
}
