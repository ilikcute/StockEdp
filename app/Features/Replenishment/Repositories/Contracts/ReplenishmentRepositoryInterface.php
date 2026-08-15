<?php

namespace App\Features\Replenishment\Repositories\Contracts;

use App\Features\Location\Models\Location;
use App\Features\Replenishment\DTOs\ReplenishmentFilterData;
use Illuminate\Support\Collection;

interface ReplenishmentRepositoryInterface
{
    public function getTargetLocation(int $locationId): ?Location;

    public function isLocationFrozen(int $locationId): bool;

    /**
     * Get all low-stock candidates matching base filters (using canonical LowStockQuery).
     *
     * @param  array<int>  $allowedLocationIds
     */
    public function getLowStockCandidates(
        array $allowedLocationIds,
        ReplenishmentFilterData $filters
    ): Collection;

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
     * @return array<string, mixed>
     */
    public function getFilterOptions(array $allowedLocationIds): array;
}
