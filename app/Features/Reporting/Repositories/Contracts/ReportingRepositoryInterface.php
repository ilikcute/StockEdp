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

    public function getPaginatedStockReceiptReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    public function getStockReceiptReportSummary(array $allowedLocationIds, array $filters): array;

    public function getPaginatedStockIssueReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    public function getStockIssueReportSummary(array $allowedLocationIds, array $filters): array;

    public function getPaginatedStockTransferReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'sent_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    public function getStockTransferReportSummary(array $allowedLocationIds, array $filters): array;

    public function getPaginatedStockAdjustmentReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    public function getStockAdjustmentReportSummary(array $allowedLocationIds, array $filters): array;

    public function getPaginatedStockOpnameReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    public function getStockOpnameReportSummary(array $allowedLocationIds, array $filters): array;
}
