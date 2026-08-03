<?php

namespace App\Features\Reporting\Helpers;

use InvalidArgumentException;

class DecimalQuantity
{
    /**
     * Normalize a numeric/decimal string value to a fixed decimal precision string using BCMath.
     *
     * @throws InvalidArgumentException
     */
    public static function normalize(?string $value, int $scale = 4): string
    {
        if ($value === null || $value === '') {
            return str_repeat('0', $scale > 0 ? 1 : 0).'.'.str_repeat('0', $scale);
        }

        $value = trim($value);

        if ($value === '') {
            return str_repeat('0', $scale > 0 ? 1 : 0).'.'.str_repeat('0', $scale);
        }

        if (! preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            throw new InvalidArgumentException("Invalid decimal quantity value: [{$value}]");
        }

        $result = bcadd($value, '0', $scale);

        if ($result === '-0.'.str_repeat('0', $scale) || $result === '-0') {
            return str_repeat('0', $scale > 0 ? 1 : 0).'.'.str_repeat('0', $scale);
        }

        return $result;
    }
}
