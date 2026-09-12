<?php

namespace App\Features\Inventory\Repositories\Contracts;

use App\Features\Inventory\Models\StockIssue;
use Illuminate\Pagination\LengthAwarePaginator;

interface StockIssueRepositoryInterface
{
    public function getPaginatedIssues(array $filters, string $sortField, string $sortDirection, int $perPage): LengthAwarePaginator;

    public function findById(int $id): ?StockIssue;

    public function create(array $data): StockIssue;

    public function update(StockIssue $issue, array $data): bool;

    public function generateIssueNumber(): string;
}
