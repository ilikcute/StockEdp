<?php

namespace App\Features\Inventory\Enums;

enum AdjustmentStatus: string
{
    case DRAFT = 'DRAFT';
    case POSTED = 'POSTED';
    case CANCELED = 'CANCELED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::POSTED => 'Diposting',
            self::CANCELED => 'Dibatalkan',
        };
    }
}
