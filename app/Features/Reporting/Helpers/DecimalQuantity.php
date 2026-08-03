<?php

namespace App\Features\Reporting\Helpers;

use InvalidArgumentException;

class DecimalQuantity
{
    /**
     * Normalize a numeric/decimal value to a fixed decimal precision string using BCMath.
     *
     *
     * @throws InvalidArgumentException
     */
    public static function normalize(string|int|float|null $value, int $scale = 4): string
    {
        if ($value === null) {
            return sprintf('%.*f', $scale, 0); // '0.0000'
        }

        $decimal = (string) $value;

        if (! preg_match('/^-?\d+(?:\.\d+)?$/', $decimal)) {
            throw new InvalidArgumentException("Invalid decimal quantity value: [{$decimal}]");
        }

        return bcadd($decimal, '0', $scale);
    }
}
