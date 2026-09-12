<?php

namespace App\Features\Auth\Enums;

enum RoleCode: string
{
    case ADMIN = 'ADMIN';
    case WAREHOUSE_OFFICER = 'WAREHOUSE_OFFICER';
    case INVENTORY_SUPERVISOR = 'INVENTORY_SUPERVISOR';
    case FIELD_TECHNICIAN = 'FIELD_TECHNICIAN';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::WAREHOUSE_OFFICER => 'Petugas Gudang',
            self::INVENTORY_SUPERVISOR => 'Supervisor Inventory',
            self::FIELD_TECHNICIAN => 'Teknisi Lapangan',
        };
    }
}
