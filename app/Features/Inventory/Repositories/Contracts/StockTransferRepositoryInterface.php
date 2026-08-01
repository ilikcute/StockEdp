<?php

namespace App\Features\Inventory\Repositories\Contracts;

use App\Features\Inventory\Models\StockTransfer;
use Illuminate\Pagination\LengthAwarePaginator;

interface StockTransferRepositoryInterface
{
    public function getPaginatedTransfers(array $filters, string $sortField = 'id', string $sortDirection = 'desc', int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?StockTransfer;

    public function create(array $data): StockTransfer;

    public function update(StockTransfer $transfer, array $data): StockTransfer;

    public function generateTransferNumber(): string;
}
