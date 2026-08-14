<?php

namespace App\Features\Replenishment\Enums;

enum ReplenishmentPriority: string
{
    case CRITICAL = 'CRITICAL';
    case WARNING = 'WARNING';

    public function label(): string
    {
        return match ($this) {
            self::CRITICAL => 'Kritis (Habis/Minus)',
            self::WARNING => 'Peringatan (Di Bawah Minimum)',
        };
    }
}
