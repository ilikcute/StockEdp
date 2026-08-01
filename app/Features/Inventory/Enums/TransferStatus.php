<?php

namespace App\Features\Inventory\Enums;

enum TransferStatus: string
{
    case DRAFT = 'DRAFT';
    case SENT = 'SENT';
    case RECEIVED = 'RECEIVED';
    case CANCELED = 'CANCELED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::RECEIVED => 'Received',
            self::CANCELED => 'Canceled',
        };
    }
}
