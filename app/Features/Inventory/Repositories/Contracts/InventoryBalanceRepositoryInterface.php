<?php

namespace App\Features\Inventory\Repositories\Contracts;

use App\Features\Inventory\Models\InventoryBalance;

interface InventoryBalanceRepositoryInterface
{
    /**
     * Get or create balance and lock for update.
     */
    public function lockBalanceForUpdate(int $productId, int $locationId, string $condition = 'GOOD'): InventoryBalance;

    /**
     * Get balance without locking.
     */
    public function getBalance(int $productId, int $locationId, string $condition = 'GOOD'): ?InventoryBalance;

    /**
     * Get paginated balances.
     */
    public function getPaginatedBalances(array $filters, string $sortField = 'id', string $sortDirection = 'desc', int $perPage = 15);
}
