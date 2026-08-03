<?php

declare(strict_types=1);

namespace App\Features\Reporting\Helpers;

use InvalidArgumentException;
use TypeError;

/**
 * Helper class for normalizing decimal quantity values.
 *
 * Only accepts string|null to prevent float precision issues.
 * Rejects float, int, bool, array, object from any caller (strict or non-strict).
 * Empty string and whitespace-only strings are rejected as invalid.
 * Output is always a fixed 4-decimal-place string.
 */
final class DecimalQuantity
{
    private const SCALE = 4;

    public static function normalize(mixed $value): string
    {
        // Explicitly reject null-unlike types at runtime, even from non-strict callers
        if ($value === null) {
            return '0.0000';
        }

        // Reject any non-string type (float, int, bool, array, object, resource)
        // This works even when called from non-strict mode because we check runtime type
        if (! is_string($value)) {
            throw new TypeError(sprintf(
                '%s::normalize(): Argument #1 ($value) must be of type string|null, %s given',
                self::class,
                get_debug_type($value),
            ));
        }

        // Trim whitespace
        $value = trim($value);

        // Empty string after trim is invalid (not treated as zero)
        if ($value === '') {
            throw new InvalidArgumentException(
                'Invalid decimal quantity value: empty string.'
            );
        }

        // Validate decimal format: optional minus, digits, optional decimal point with digits
        // Rejects: scientific notation (1e4), comma decimals (1,25), multiple dots (1.2.3), etc.
        if (! preg_match('/^-?\d+(?:\.\d+)?$/D', $value)) {
            throw new InvalidArgumentException(
                "Invalid decimal quantity value: [{$value}]"
            );
        }

        $normalized = bcadd($value, '0', self::SCALE);

        if (bccomp($normalized, '0', self::SCALE) === 0) {
            return '0.0000';
        }

        return $normalized;
    }
}
