<?php

namespace App\Features\Inventory\Services;

use App\Features\Inventory\Repositories\Contracts\InventoryBalanceRepositoryInterface;

class StockAvailabilityService
{
    public function __construct(
        private readonly InventoryBalanceRepositoryInterface $balanceRepository
    ) {}

    /**
     * Check if a specific quantity is available for a product at a location.
     */
    public function isAvailable(int $productId, int $locationId, string $quantity): bool
    {
        if (bccomp($quantity, '0.0000', 4) <= 0) {
            return false;
        }

        $balance = $this->balanceRepository->getBalance($productId, $locationId);

        if (! $balance) {
            return false;
        }

        return bccomp($balance->quantity, $quantity, 4) >= 0;
    }
}
