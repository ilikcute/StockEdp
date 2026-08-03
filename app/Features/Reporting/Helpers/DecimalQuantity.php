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
        if ($value === null) {
            return '0.' . str_repeat('0', $scale);
        }

        $decimal = trim($value);

        if (! preg_match('/^-?\d+(?:\.\d+)?$/', $decimal)) {
            throw new InvalidArgumentException("Invalid decimal quantity value: [{$decimal}]");
        }

        $result = bcadd($decimal, '0', $scale);

        // Normalize negative zero e.g. "-0.0000" to "0.0000"
        if (bccomp($result, '0', $scale) === 0) {
            return '0.' . str_repeat('0', $scale);
        }

        return $result;
    }
}
