<?php

namespace App\Features\Reporting\Exports;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class XlsxStreamWriter
{
    /**
     * Stream an Excel (.xlsx) file to the output stream.
     *
     * @param  string  $outputPath  Path to write to (typically 'php://output')
     * @param  array<string>  $headers
     * @param  iterable  $rows  Iterator of rows (each row is an array of values)
     */
    public static function writeStream(string $outputPath, array $headers, iterable $rows): void
    {
        $writer = new Writer();
        $writer->openToFile($outputPath);

        // Header style: bold, 11pt, white text on brand Indigo background
        $headerStyle = new Style(
            fontBold: true,
            fontSize: 11,
            fontColor: Color::WHITE,
            backgroundColor: '3730A3' // Indigo 800
        );

        $sanitizedHeaders = array_map([self::class, 'sanitizeHeader'], $headers);
        $writer->addRow(Row::fromValuesWithStyle($sanitizedHeaders, $headerStyle));

        foreach ($rows as $row) {
            $rowValues = is_array($row) ? array_values($row) : (array) $row;
            $sanitizedRow = array_map([self::class, 'sanitizeValue'], $rowValues);
            $writer->addRow(Row::fromValues($sanitizedRow));
        }

        $writer->close();
    }

    public static function sanitizeHeader(string $header): string
    {
        return CsvStreamWriter::sanitizeHeader($header);
    }

    public static function sanitizeValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $str = (string) $value;

        // If string is pure integer with leading zero (e.g. "0123", "005") and length > 1, preserve as string
        if (preg_match('/^0\d+$/', $str)) {
            return CsvStreamWriter::sanitizeText($str);
        }

        // If string matches numeric decimal (e.g. "0", "0.0000", "-0.0001", "123.45", "-50"), convert to numeric
        if (CsvStreamWriter::isNumericDecimal($str)) {
            return str_contains($str, '.') ? (float) $str : (int) $str;
        }

        return CsvStreamWriter::sanitizeText($str);
    }
}
