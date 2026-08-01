<?php

namespace App\Features\Inventory\Repositories\Contracts;

use App\Features\Inventory\Models\StockMovement;

interface StockMovementRepositoryInterface
{
    /**
     * Create a new stock movement record.
     */
    public function create(array $data): StockMovement;

    /**
     * Get paginated movements.
     */
    public function getPaginatedMovements(array $filters, string $sortField = 'created_at', string $sortDirection = 'desc', int $perPage = 15);
}
