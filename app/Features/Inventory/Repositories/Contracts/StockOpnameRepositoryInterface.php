<?php

namespace App\Features\Inventory\Repositories\Contracts;

use App\Features\Inventory\Models\StockOpname;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StockOpnameRepositoryInterface
{
    public function getPaginatedOpnames(
        array $allowedLocationIds,
        array $filters = [],
        string $sortField = 'id',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    public function findById(int $id): ?StockOpname;

    public function generateOpnameNumber(): string;
}
