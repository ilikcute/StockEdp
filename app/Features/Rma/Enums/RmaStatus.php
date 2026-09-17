<?php

namespace App\Features\Rma\Enums;

enum RmaStatus: string
{
    case DRAFT = 'DRAFT';
    case DISPATCHED = 'DISPATCHED';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::DISPATCHED => 'Dikirim ke Vendor',
            self::COMPLETED => 'Selesai / Diterima Kembali',
            self::CANCELLED => 'Dibatalkan',
        };
    }
}
