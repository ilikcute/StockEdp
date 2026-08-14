<?php

namespace App\Features\Replenishment\Repositories\Contracts;

use App\Features\Location\Models\Location;
use App\Features\Replenishment\DTOs\ReplenishmentFilterData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReplenishmentRepositoryInterface
{
    public function getTargetLocation(int $locationId): ?Location;

    public function isLocationFrozen(int $locationId): bool;

    public function getPaginatedLowStock(
        array $allowedLocationIds,
        ReplenishmentFilterData $filters
    ): LengthAwarePaginator;

    /**
     * @param  array<int>  $productIds
     * @return array<int, string> Map of product_id => pending_inbound_quantity
     */
    public function getPendingInboundQuantities(
        int $targetLocationId,
        array $productIds
    ): array;

    /**
     * @param  array<int>  $allowedLocationIds
     * @param  array<int>  $productIds
     * @return array<int, array<object>> Grouped source balances per product_id
     */
    public function getCandidateSourceBalances(
        int $targetLocationId,
        array $allowedLocationIds,
        array $productIds
    ): array;

    /**
     * @param  array<int>  $allowedLocationIds
     * @return array<string, int>
     */
    public function calculateSummaryCounts(
        int $targetLocationId,
        array $allowedLocationIds,
        ReplenishmentFilterData $filters
    ): array;

    /**
     * @param  array<int>  $allowedLocationIds
     * @return array<string, mixed>
     */
    public function getFilterOptions(array $allowedLocationIds): array;
}
