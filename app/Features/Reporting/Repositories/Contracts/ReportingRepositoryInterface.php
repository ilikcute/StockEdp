<?php

namespace App\Features\Reporting\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

interface ReportingRepositoryInterface
{
    public function getBaseLocations(array $allowedLocationIds): Collection;

    public function getActiveCategories(): Collection;

    public function getActiveUnits(): Collection;

    public function searchProductOptions(?string $search, int $perPage = 20): Collection;

    public function searchSupplierOptions(?string $search, int $perPage = 20): Collection;

    public function getPaginatedBalances(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'id',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    public function getCursorBalances(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'id',
        string $sortDirection = 'desc'
    ): LazyCollection;

    public function getPaginatedLowStock(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'shortage_quantity',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    public function getCursorLowStock(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'shortage_quantity',
        string $sortDirection = 'desc'
    ): LazyCollection;

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

    public function getCursorStockCardMovements(
        int $productId,
        int $locationId,
        string $startDateTime,
        string $endNextDayDateTime
    ): LazyCollection;

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

    public function getCursorStockReceiptReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc'
    ): LazyCollection;

    public function getStockReceiptReportSummary(array $allowedLocationIds, array $filters): array;

    public function getPaginatedStockIssueReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    public function getCursorStockIssueReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc'
    ): LazyCollection;

    public function getStockIssueReportSummary(array $allowedLocationIds, array $filters): array;

    public function getPaginatedStockTransferReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'sent_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    public function getCursorStockTransferReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'sent_at',
        string $sortDirection = 'desc'
    ): LazyCollection;

    public function getStockTransferReportSummary(array $allowedLocationIds, array $filters): array;

    public function getPaginatedStockAdjustmentReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    public function getCursorStockAdjustmentReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc'
    ): LazyCollection;

    public function getStockAdjustmentReportSummary(array $allowedLocationIds, array $filters): array;

    public function getPaginatedStockOpnameReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    public function getCursorStockOpnameReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc'
    ): LazyCollection;

    public function getStockOpnameReportSummary(array $allowedLocationIds, array $filters): array;
}
