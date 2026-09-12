<?php

namespace App\Features\Location\Enums;

enum LocationType: string
{
    case MAIN_WAREHOUSE = 'MAIN_WAREHOUSE';
    case FIELD_PERSONNEL = 'FIELD_PERSONNEL';
    case DAMAGED_STORAGE = 'DAMAGED_STORAGE';

    public function label(): string
    {
        return match ($this) {
            self::MAIN_WAREHOUSE => 'Gudang Induk',
            self::FIELD_PERSONNEL => 'Stok Operasional (Personel Lapangan)',
            self::DAMAGED_STORAGE => 'Gudang Isolasi Afkir / Rusak',
        };
    }

    public function isFieldPersonnel(): bool
    {
        return $this === self::FIELD_PERSONNEL;
    }
}
