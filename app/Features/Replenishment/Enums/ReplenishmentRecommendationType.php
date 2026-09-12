<?php

namespace App\Features\Replenishment\Enums;

enum ReplenishmentRecommendationType: string
{
    case INBOUND_COVERED = 'INBOUND_COVERED';
    case INTERNAL_TRANSFER = 'INTERNAL_TRANSFER';
    case MIXED = 'MIXED';
    case EXTERNAL_REORDER = 'EXTERNAL_REORDER';

    public function label(): string
    {
        return match ($this) {
            self::INBOUND_COVERED => 'Ditutup Inbound',
            self::INTERNAL_TRANSFER => 'Transfer Internal',
            self::MIXED => 'Sebagian Transfer & Reorder',
            self::EXTERNAL_REORDER => 'Reorder Eksternal',
        };
    }
}
