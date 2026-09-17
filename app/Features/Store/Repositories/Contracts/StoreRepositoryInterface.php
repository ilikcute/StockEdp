<?php

namespace App\Features\Store\Repositories\Contracts;

use App\Features\Store\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StoreRepositoryInterface
{
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Store;

    public function findByCode(string $code): ?Store;

    public function create(array $data): Store;

    public function update(Store $store, array $data): Store;
}
