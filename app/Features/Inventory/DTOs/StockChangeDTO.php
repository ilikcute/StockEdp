<?php

namespace App\Features\Inventory\DTOs;

use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Enums\StockCondition;

readonly class StockChangeDTO
{
    public StockCondition $condition;

    public function __construct(
        public int $productId,
        public int $locationId,
        public string $quantity,
        public MovementType $movementType,
        public string $referenceType,
        public int $referenceId,
        public ?string $referenceNumber = null,
        public ?int $userId = null,
        public ?string $occurredAt = null,
        StockCondition|string $condition = StockCondition::GOOD,
    ) {
        $this->condition = is_string($condition)
            ? (StockCondition::tryFrom($condition) ?? StockCondition::GOOD)
            : $condition;
    }
}
