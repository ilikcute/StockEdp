<?php

namespace Tests\Feature\MasterDataImport;

use App\Features\MasterDataImport\Readers\CsvMasterDataImportReader;
use App\Shared\Exceptions\DomainException;
use Tests\TestCase;

class CsvMasterDataImportReaderTest extends TestCase
{
    protected CsvMasterDataImportReader $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reader = new CsvMasterDataImportReader;
    }

    public function test_csv_01_and_csv_03_and_csv_04_parses_utf8_bom_crlf_quoted_comma(): void
    {
        $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
        $csv = $bom."code,name,description\r\nCAT-01,\"Electronics, Audio & Video\",Best electronics\r\n\r\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempFile, $csv);

        $result = $this->reader->read($tempFile);
        unlink($tempFile);

        $this->assertSame(['code', 'name', 'description'], $result['headers']);
        $this->assertSame(1, $result['total_rows']);
        $this->assertSame('CAT-01', $result['rows'][0]['data']['code']);
        $this->assertSame('Electronics, Audio & Video', $result['rows'][0]['data']['name']);
    }

    public function test_csv_02_parses_csv_with_lf(): void
    {
        $csv = "code,name,description\nCAT-01,Name 1,Desc 1\nCAT-02,Name 2,Desc 2\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempFile, $csv);

        $result = $this->reader->read($tempFile);
        unlink($tempFile);

        $this->assertSame(2, $result['total_rows']);
        $this->assertSame('CAT-01', $result['rows'][0]['data']['code']);
        $this->assertSame('CAT-02', $result['rows'][1]['data']['code']);
    }

    public function test_csv_05_and_csv_06_parses_escaped_quote_and_blank_optional_field(): void
    {
        $csv = "code,name,description\nCAT-01,\"Item with \"\"quotes\"\"\",\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempFile, $csv);

        $result = $this->reader->read($tempFile);
        unlink($tempFile);

        $this->assertSame(1, $result['total_rows']);
        $this->assertSame('Item with "quotes"', $result['rows'][0]['data']['name']);
        $this->assertNull($result['rows'][0]['data']['description']);
    }

    public function test_csv_07_ignores_trailing_blank_rows(): void
    {
        $csv = "code,name,description\nCAT-01,Name 1,Desc 1\n\n   \n\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempFile, $csv);

        $result = $this->reader->read($tempFile);
        unlink($tempFile);

        $this->assertSame(1, $result['total_rows']);
    }

    public function test_csv_08_rejects_empty_file(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempFile, '');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('File CSV kosong atau tidak memiliki header.');

        try {
            $this->reader->read($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function test_csv_09_rejects_malformed_csv_row_shape(): void
    {
        $csv = "code,name,description\nCAT-01,Only Name\n";
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempFile, $csv);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Struktur baris 2 tidak sesuai dengan jumlah kolom header');

        try {
            $this->reader->read($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function test_hdr_03_rejects_duplicate_column_headers(): void
    {
        $csv = "code,name,code\nCAT-01,Name,CAT-01\n";
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempFile, $csv);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Header CSV memiliki kolom duplikat: 'code'.");

        try {
            $this->reader->read($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function test_csv_10_accepts_exactly_5000_rows(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        $fp = fopen($tempFile, 'w');
        fputcsv($fp, ['code', 'name', 'description']);

        for ($i = 1; $i <= 5000; $i++) {
            fputcsv($fp, ["CAT-{$i}", "Name {$i}", 'Desc']);
        }
        fclose($fp);

        $result = $this->reader->read($tempFile);
        unlink($tempFile);

        $this->assertSame(5000, $result['total_rows']);
    }

    public function test_csv_11_rejects_more_than_5000_rows(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        $fp = fopen($tempFile, 'w');
        fputcsv($fp, ['code', 'name', 'description']);

        for ($i = 1; $i <= 5001; $i++) {
            fputcsv($fp, ["CAT-{$i}", "Name {$i}", 'Desc']);
        }
        fclose($fp);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Jumlah baris data melebihi batas maksimum 5.000 baris.');

        try {
            $this->reader->read($tempFile);
        } finally {
            unlink($tempFile);
        }
    }
}
