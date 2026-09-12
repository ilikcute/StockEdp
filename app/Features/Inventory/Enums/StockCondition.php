<?php

namespace App\Features\Inventory\Enums;

enum StockCondition: string
{
    case GOOD = 'GOOD';
    case DEFECTIVE = 'DEFECTIVE';

    public function label(): string
    {
        return match ($this) {
            self::GOOD => 'Baik',
            self::DEFECTIVE => 'Rusak / Afkir',
        };
    }

    public function isGood(): bool
    {
        return $this === self::GOOD;
    }
}
