<?php

namespace App\Features\Inventory\Enums;

enum MovementType: string
{
    case RECEIPT = 'RECEIPT';
    case ISSUE = 'ISSUE';
    case TRANSFER_IN = 'TRANSFER_IN';
    case TRANSFER_OUT = 'TRANSFER_OUT';
    case ADJUSTMENT_IN = 'ADJUSTMENT_IN';
    case ADJUSTMENT_OUT = 'ADJUSTMENT_OUT';
    case OPNAME_IN = 'OPNAME_IN';
    case OPNAME_OUT = 'OPNAME_OUT';
    case REVERSAL = 'REVERSAL';

    public function label(): string
    {
        return match ($this) {
            self::RECEIPT => 'Penerimaan',
            self::ISSUE => 'Pengeluaran',
            self::TRANSFER_IN => 'Transfer Masuk',
            self::TRANSFER_OUT => 'Transfer Keluar',
            self::ADJUSTMENT_IN => 'Penyesuaian Masuk',
            self::ADJUSTMENT_OUT => 'Penyesuaian Keluar',
            self::OPNAME_IN => 'Opname Masuk',
            self::OPNAME_OUT => 'Opname Keluar',
            self::REVERSAL => 'Pembatalan',
        };
    }

    public function isAddition(): bool
    {
        return in_array($this, [
            self::RECEIPT,
            self::TRANSFER_IN,
            self::ADJUSTMENT_IN,
            self::OPNAME_IN,
        ], true);
    }
}
