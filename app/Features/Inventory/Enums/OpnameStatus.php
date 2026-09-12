<?php

namespace App\Features\Inventory\Enums;

enum OpnameStatus: string
{
    case DRAFT = 'DRAFT';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COUNTED = 'COUNTED';
    case POSTED = 'POSTED';
    case CANCELED = 'CANCELED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::IN_PROGRESS => 'Sedang Dihitung',
            self::COUNTED => 'Selesai Dihitung',
            self::POSTED => 'Diposting',
            self::CANCELED => 'Dibatalkan',
        };
    }
}
