<?php

namespace App\Features\Reporting\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReportingRepositoryInterface
{
    public function getPaginatedBalances(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'id',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    public function getPaginatedLowStock(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'shortage_quantity',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    public function getOpeningBalanceForStockCard(
        int $productId,
        int $locationId,
        string $startDateTime
    ): string;

    public function getPaginatedStockCardMovements(
        int $productId,
        int $locationId,
        string $startDateTime,
        string $endNextDayDateTime,
        int $perPage = 15
    ): LengthAwarePaginator;

    public function getStockCardSummary(
        int $productId,
        int $locationId,
        string $startDateTime,
        string $endNextDayDateTime,
        string $openingBalance
    ): array;
}
