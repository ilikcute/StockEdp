<?php

namespace App\Features\Inventory\Enums;

enum TransferStatus: string
{
    case DRAFT = 'DRAFT';
    case IN_TRANSIT = 'IN_TRANSIT';
    case RECEIVED = 'RECEIVED';
    case DISCREPANCY = 'DISCREPANCY';
    case CANCELED = 'CANCELED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::IN_TRANSIT => 'In Transit',
            self::RECEIVED => 'Received',
            self::DISCREPANCY => 'Selisih',
            self::CANCELED => 'Canceled',
        };
    }
}
