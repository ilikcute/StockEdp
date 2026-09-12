<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Features\Reporting\Helpers\DecimalQuantity;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TypeError;

class DecimalQuantityHelperTest extends TestCase
{
    public function test_normalize_returns_zero_for_null(): void
    {
        $this->assertSame('0.0000', DecimalQuantity::normalize(null));
    }

    public function test_normalize_handles_valid_decimal_strings(): void
    {
        $this->assertSame('0.0000', DecimalQuantity::normalize('0'));
        $this->assertSame('0.0000', DecimalQuantity::normalize('0.0'));
        $this->assertSame('0.0000', DecimalQuantity::normalize('0.0000'));
        $this->assertSame('0.0001', DecimalQuantity::normalize('0.0001'));
        $this->assertSame('-0.0001', DecimalQuantity::normalize('-0.0001'));
        $this->assertSame('-9.9999', DecimalQuantity::normalize('-9.9999'));
        $this->assertSame('9999999999.9999', DecimalQuantity::normalize('9999999999.9999'));
    }

    public function test_normalize_converts_negative_zero_to_positive(): void
    {
        $this->assertSame('0.0000', DecimalQuantity::normalize('-0'));
        $this->assertSame('0.0000', DecimalQuantity::normalize('-0.0'));
        $this->assertSame('0.0000', DecimalQuantity::normalize('-0.0000'));
        $this->assertSame('0.0000', DecimalQuantity::normalize('-0.00'));
        $this->assertSame('0.0000', DecimalQuantity::normalize('-000.0000'));
        $this->assertSame('0.0000', DecimalQuantity::normalize('-0000'));
    }

    public function test_normalize_rejects_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid decimal quantity value: empty string.');

        DecimalQuantity::normalize('');
    }

    public function test_normalize_rejects_whitespace_only_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DecimalQuantity::normalize('   ');
    }

    public function test_normalize_rejects_invalid_decimal_formats(): void
    {
        $invalidValues = [
            'abc',
            '1,25',
            '1.2.3',
            '--1',
            '+-1',
            'NaN',
            'INF',
            '1e4',
            '1E4',
            '-1e-4',
            '+1',
            '1.',
            '.5',
        ];

        foreach ($invalidValues as $value) {
            try {
                DecimalQuantity::normalize($value);
                $this->fail("Expected InvalidArgumentException for value: {$value}");
            } catch (InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_normalize_rejects_float_from_strict_caller(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('must be of type string|null, float given');

        DecimalQuantity::normalize(0.1);
    }

    public function test_normalize_rejects_int_from_strict_caller(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('must be of type string|null, int given');

        DecimalQuantity::normalize(1);
    }

    public function test_normalize_rejects_bool_from_strict_caller(): void
    {
        $this->expectException(TypeError::class);

        DecimalQuantity::normalize(true);
    }

    public function test_normalize_rejects_array_from_strict_caller(): void
    {
        $this->expectException(TypeError::class);

        DecimalQuantity::normalize([]);
    }

    public function test_normalize_rejects_object_from_strict_caller(): void
    {
        $this->expectException(TypeError::class);

        DecimalQuantity::normalize(new \stdClass);
    }

    public function test_normalize_pads_fractional_part_to_four_decimals(): void
    {
        $this->assertSame('1.0000', DecimalQuantity::normalize('1'));
        $this->assertSame('1.5000', DecimalQuantity::normalize('1.5'));
        $this->assertSame('1.2500', DecimalQuantity::normalize('1.25'));
        $this->assertSame('1.2340', DecimalQuantity::normalize('1.234'));
    }

    public function test_normalize_truncates_excessive_decimal_places(): void
    {
        $this->assertSame('1.2345', DecimalQuantity::normalize('1.23456'));
        $this->assertSame('1.2345', DecimalQuantity::normalize('1.23459'));
        $this->assertSame('9999999999.9999', DecimalQuantity::normalize('9999999999.99999999'));
    }

    public function test_normalize_handles_negative_values_correctly(): void
    {
        $this->assertSame('-1.0000', DecimalQuantity::normalize('-1'));
        $this->assertSame('-1.5000', DecimalQuantity::normalize('-1.5'));
        $this->assertSame('-0.0001', DecimalQuantity::normalize('-0.0001'));
        $this->assertSame('-999.9999', DecimalQuantity::normalize('-999.9999'));
    }
}
