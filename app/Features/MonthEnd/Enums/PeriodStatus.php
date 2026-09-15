<?php

namespace App\Features\MonthEnd\Enums;

enum PeriodStatus: string
{
    case OPEN = 'OPEN';
    case CLOSED = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Terbuka (Open)',
            self::CLOSED => 'Ditutup (Closed)',
        };
    }
}
