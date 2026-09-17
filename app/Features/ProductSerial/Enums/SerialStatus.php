<?php

namespace App\Features\ProductSerial\Enums;

enum SerialStatus: string
{
    case IN_STOCK = 'IN_STOCK';
    case INSTALLED = 'INSTALLED';
    case DEFECTIVE = 'DEFECTIVE';
    case RETURNED_TO_VENDOR = 'RETURNED_TO_VENDOR';
    case DISPOSED = 'DISPOSED';

    public function label(): string
    {
        return match ($this) {
            self::IN_STOCK => 'Dalam Stok',
            self::INSTALLED => 'Terpasang di Toko',
            self::DEFECTIVE => 'Rusak (Defective)',
            self::RETURNED_TO_VENDOR => 'Dikirim ke Vendor (RMA)',
            self::DISPOSED => 'Dimusnahkan / Afkir',
        };
    }
}
