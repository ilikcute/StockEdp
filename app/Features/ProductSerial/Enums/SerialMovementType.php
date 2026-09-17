<?php

namespace App\Features\ProductSerial\Enums;

enum SerialMovementType: string
{
    case INITIAL_REGISTRATION = 'INITIAL_REGISTRATION';
    case RECEIPT = 'RECEIPT';
    case TRANSFER = 'TRANSFER';
    case STORE_ALLOCATION_INSTALL = 'STORE_ALLOCATION_INSTALL';
    case STORE_ALLOCATION_PULL = 'STORE_ALLOCATION_PULL';
    case RMA_DISPATCH = 'RMA_DISPATCH';
    case RMA_RETURN = 'RMA_RETURN';
    case STATUS_CHANGE = 'STATUS_CHANGE';

    public function label(): string
    {
        return match ($this) {
            self::INITIAL_REGISTRATION => 'Registrasi Awal',
            self::RECEIPT => 'Penerimaan Barang',
            self::TRANSFER => 'Transfer Antar Lokasi',
            self::STORE_ALLOCATION_INSTALL => 'Pemasangan di Toko',
            self::STORE_ALLOCATION_PULL => 'Penarikan Unit Rusak dari Toko',
            self::RMA_DISPATCH => 'Pengiriman RMA ke Vendor',
            self::RMA_RETURN => 'Penerimaan Kembali Hasil Servis',
            self::STATUS_CHANGE => 'Perubahan Status Manual',
        };
    }
}
