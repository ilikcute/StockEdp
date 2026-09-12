<?php

namespace App\Features\Inventory\Repositories\Contracts;

use App\Features\Inventory\Models\StockReceipt;
use Illuminate\Pagination\LengthAwarePaginator;

interface StockReceiptRepositoryInterface
{
    public function getPaginatedReceipts(array $filters, string $sortField, string $sortDirection, int $perPage): LengthAwarePaginator;

    public function findById(int $id): ?StockReceipt;

    public function create(array $data): StockReceipt;

    public function update(StockReceipt $receipt, array $data): bool;

    public function generateReceiptNumber(): string;
}
