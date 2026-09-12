<?php

namespace App\Features\Reporting\Exports;

class CsvStreamWriter
{
    /**
     * Write UTF-8 BOM, header, and rows to the given output stream handle.
     *
     * @param  resource  $handle
     * @param  array<string>  $headers
     * @param  iterable  $rows  Iterator of rows (each row is an array of values)
     */
    public static function writeStream($handle, array $headers, iterable $rows): void
    {
        // 1. Output UTF-8 BOM
        fwrite($handle, "\xEF\xBB\xBF");

        // 2. Write Header
        fputcsv($handle, array_map([self::class, 'sanitizeHeader'], $headers));

        // 3. Stream Rows
        foreach ($rows as $row) {
            $sanitizedRow = array_map([self::class, 'sanitizeValue'], $row);
            fputcsv($handle, $sanitizedRow);
        }
    }

    public static function sanitizeHeader(string $header): string
    {
        return self::sanitizeText($header);
    }

    public static function sanitizeValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $str = (string) $value;

        // If string matches a decimal number (e.g., "0.0000", "-0.0001", "123.45", "-50"), do NOT prefix with apostrophe.
        if (self::isNumericDecimal($str)) {
            return $str;
        }

        return self::sanitizeText($str);
    }

    public static function isNumericDecimal(string $value): bool
    {
        return (bool) preg_match('/^-?\d+(\.\d+)?$/', $value);
    }

    public static function sanitizeText(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $firstChar = $value[0];
        if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
