<?php

namespace App\Features\Inventory\Enums;

enum MovementType: string
{
    case RECEIPT = 'RECEIPT';
    case RECEIPT_GA = 'RECEIPT_GA';
    case ISSUE = 'ISSUE';
    case TRANSFER_IN = 'TRANSFER_IN';
    case TRANSFER_OUT = 'TRANSFER_OUT';
    case STORE_ALLOCATION = 'STORE_ALLOCATION';
    case REPLACEMENT_PULL = 'REPLACEMENT_PULL';
    case RETURN_TO_WAREHOUSE = 'RETURN_TO_WAREHOUSE';
    case ADJUSTMENT_IN = 'ADJUSTMENT_IN';
    case ADJUSTMENT_OUT = 'ADJUSTMENT_OUT';
    case OPNAME_IN = 'OPNAME_IN';
    case OPNAME_OUT = 'OPNAME_OUT';
    case REVERSAL = 'REVERSAL';
    case RMA_DISPATCH = 'RMA_DISPATCH';

    public function label(): string
    {
        return match ($this) {
            self::RECEIPT => 'Penerimaan',
            self::RECEIPT_GA => 'Penerimaan GA',
            self::ISSUE => 'Pengeluaran',
            self::TRANSFER_IN => 'Transfer Masuk',
            self::TRANSFER_OUT => 'Transfer Keluar',
            self::STORE_ALLOCATION => 'Alokasi Toko',
            self::REPLACEMENT_PULL => 'Tarik Unit Bekas',
            self::RETURN_TO_WAREHOUSE => 'Retur ke Gudang',
            self::ADJUSTMENT_IN => 'Penyesuaian Masuk',
            self::ADJUSTMENT_OUT => 'Penyesuaian Keluar',
            self::OPNAME_IN => 'Opname Masuk',
            self::OPNAME_OUT => 'Opname Keluar',
            self::REVERSAL => 'Pembatalan',
            self::RMA_DISPATCH => 'Klaim RMA Vendor',
        };
    }

    public function isAddition(): bool
    {
        return match ($this) {
            self::RECEIPT,
            self::RECEIPT_GA,
            self::TRANSFER_IN,
            self::REPLACEMENT_PULL,
            self::RETURN_TO_WAREHOUSE,
            self::ADJUSTMENT_IN,
            self::OPNAME_IN => true,
            default => false,
        };
    }
}
