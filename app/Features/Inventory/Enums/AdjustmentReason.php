<?php

namespace App\Features\Inventory\Enums;

enum AdjustmentReason: string
{
    case FOUND = 'FOUND';
    case DAMAGED = 'DAMAGED';
    case EXPIRED = 'EXPIRED';
    case RECORDING_ERROR = 'RECORDING_ERROR';
    case ADMINISTRATIVE = 'ADMINISTRATIVE';
    case LOST = 'LOST';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::FOUND => 'Barang ditemukan',
            self::DAMAGED => 'Barang rusak',
            self::EXPIRED => 'Barang kedaluwarsa',
            self::RECORDING_ERROR => 'Kesalahan pencatatan',
            self::ADMINISTRATIVE => 'Koreksi administratif',
            self::LOST => 'Kehilangan barang',
            self::OTHER => 'Lain-lain',
        };
    }

    /**
     * Returns the allowed directions for this reason.
     * null means both directions are allowed.
     *
     * @return string[]|null
     */
    public function allowedDirections(): ?array
    {
        return match ($this) {
            self::FOUND => ['INCREASE'],
            self::DAMAGED => ['DECREASE'],
            self::EXPIRED => ['DECREASE'],
            self::LOST => ['DECREASE'],
            self::RECORDING_ERROR, self::ADMINISTRATIVE, self::OTHER => null, // both allowed
        };
    }

    /**
     * Check if this reason is compatible with the given direction.
     */
    public function isCompatibleWith(string $direction): bool
    {
        $allowed = $this->allowedDirections();

        if ($allowed === null) {
            return true;
        }

        return in_array($direction, $allowed, true);
    }

    /**
     * Check if this reason requires notes to be present.
     */
    public function requiresNotes(): bool
    {
        return $this === self::OTHER;
    }

    /**
     * Get all reason values as strings for validation.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
