<?php

namespace Tests\Feature\Reporting;

use App\Features\Reporting\Exports\CsvStreamWriter;
use Tests\TestCase;

class CsvStreamWriterTest extends TestCase
{
    public function test_formula_injection_protection_prefixes_apostrophe()
    {
        $dangerousInputs = [
            '=SUM(A1:A2)',
            '+CMD',
            '-TEST',
            '@IMPORT',
            "\tCOMMAND",
            "\rCOMMAND",
        ];

        foreach ($dangerousInputs as $input) {
            $sanitized = CsvStreamWriter::sanitizeValue($input);
            $this->assertStringStartsWith("'", $sanitized, "Failed for input: {$input}");
        }
    }

    public function test_decimal_numbers_are_preserved_without_apostrophe()
    {
        $decimals = [
            '0',
            '0.0000',
            '0.0001',
            '-0.0001',
            '-9.9999',
            '9999999999.9999',
            '123.45',
            '-50',
        ];

        foreach ($decimals as $dec) {
            $sanitized = CsvStreamWriter::sanitizeValue($dec);
            $this->assertEquals($dec, $sanitized, "Decimal should be preserved identical: {$dec}");
            $this->assertStringStartsNotWith("'", $sanitized);
        }
    }

    public function test_ordinary_text_is_unchanged()
    {
        $texts = [
            'Produk A',
            'SKU-001',
            'Gudang Utama',
            '100 Pcs',
        ];

        foreach ($texts as $text) {
            $sanitized = CsvStreamWriter::sanitizeValue($text);
            $this->assertEquals($text, $sanitized);
        }
    }

    public function test_csv_writer_handles_escaping_and_special_characters_roundtrip()
    {
        $headers = ['Nama', 'Alamat', 'Catatan'];
        $rows = [
            ['Budi, S.Kom', 'Jl. Merdeka "No. 1"', "Baris 1\nBaris 2"],
            ['=SUM(1,2)', '-0.0001', 'Normal'],
        ];

        $handle = fopen('php://memory', 'r+');
        CsvStreamWriter::writeStream($handle, $headers, $rows);
        rewind($handle);

        $bom = fread($handle, 3);
        $this->assertEquals("\xEF\xBB\xBF", $bom);

        $parsedHeader = fgetcsv($handle);
        $this->assertEquals($headers, $parsedHeader);

        $parsedRow1 = fgetcsv($handle);
        $this->assertEquals(['Budi, S.Kom', 'Jl. Merdeka "No. 1"', "Baris 1\nBaris 2"], $parsedRow1);

        $parsedRow2 = fgetcsv($handle);
        $this->assertEquals(["'=SUM(1,2)", '-0.0001', 'Normal'], $parsedRow2);

        fclose($handle);
    }
}
