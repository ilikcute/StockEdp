<?php

namespace App\Features\Supplier\Repositories\Contracts;

use App\Features\Supplier\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SupplierRepositoryInterface
{
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getAllActive(): Collection;

    public function findById(int $id): ?Supplier;

    public function create(array $data): Supplier;

    public function update(Supplier $supplier, array $data): Supplier;
}
