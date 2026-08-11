<?php

namespace App\Features\Dashboard\Repositories\Contracts;

interface OperationalDashboardRepositoryInterface
{
    /**
     * Get inventory health summary counts.
     */
    public function getInventoryHealth(array $allowedLocationIds, ?int $locationId = null): array;

    /**
     * Get operational queue counts.
     */
    public function getOperationalQueue(array $allowedLocationIds, ?int $locationId = null): array;

    /**
     * Get period activity summary counts.
     */
    public function getPeriodActivity(array $allowedLocationIds, ?int $locationId, string $dateFrom, string $dateTo): array;

    /**
     * Get 10 most recent stock movements.
     */
    public function getRecentActivity(array $allowedLocationIds, ?int $locationId): array;

    /**
     * Get top 10 issued products in period.
     */
    public function getTopIssuedProducts(array $allowedLocationIds, ?int $locationId, string $dateFrom, string $dateTo): array;

    /**
     * Get top 10 received products in period.
     */
    public function getTopReceivedProducts(array $allowedLocationIds, ?int $locationId, string $dateFrom, string $dateTo): array;

    /**
     * Get assignment-scoped location filter options for dashboard.
     */
    public function getFilterOptions(array $allowedLocationIds): array;
}
