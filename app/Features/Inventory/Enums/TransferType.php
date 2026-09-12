<?php

namespace App\Features\Inventory\Enums;

enum TransferType: string
{
    case TRANSFER = 'TRANSFER';
    case RETURN = 'RETURN';

    public function label(): string
    {
        return match ($this) {
            self::TRANSFER => 'Transfer Stok',
            self::RETURN => 'Retur ke Gudang',
        };
    }

    public function isReturn(): bool
    {
        return $this === self::RETURN;
    }
}
