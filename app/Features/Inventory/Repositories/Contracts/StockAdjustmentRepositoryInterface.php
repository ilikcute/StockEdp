<?php

namespace App\Features\Inventory\Repositories\Contracts;

use App\Features\Inventory\Models\StockAdjustment;
use Illuminate\Pagination\LengthAwarePaginator;

interface StockAdjustmentRepositoryInterface
{
    public function getPaginatedAdjustments(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'id',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    public function findById(int $id): ?StockAdjustment;

    public function create(array $data): StockAdjustment;

    public function update(StockAdjustment $adjustment, array $data): StockAdjustment;

    public function generateAdjustmentNumber(): string;
}
