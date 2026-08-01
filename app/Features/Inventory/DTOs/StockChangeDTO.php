<?php

namespace App\Features\Inventory\DTOs;

use App\Features\Inventory\Enums\MovementType;

readonly class StockChangeDTO
{
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
    ) {}
}
