<?php

namespace App\Features\Rma\Enums;

enum RmaItemStatus: string
{
    case PENDING = 'PENDING';
    case SERVICED = 'SERVICED';
    case REPLACED = 'REPLACED';
    case REJECTED = 'REJECTED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Servis Vendor',
            self::SERVICED => 'Telah Diservis',
            self::REPLACED => 'Diganti Unit Baru',
            self::REJECTED => 'Klaim Ditolak Vendor',
        };
    }
}
