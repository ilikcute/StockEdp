<?php

namespace App\Features\MasterDataImport\Readers;

use App\Features\MasterDataImport\Contracts\MasterDataImportReaderInterface;
use App\Shared\Exceptions\DomainException;
use SplFileObject;

class CsvMasterDataImportReader implements MasterDataImportReaderInterface
{
    public const MAX_ROWS = 5000;

    /**
     * Read and parse a CSV file.
     *
     * @return array{
     *     headers: array<int, string>,
     *     rows: array<int, array{row_number: int, data: array<string, ?string>}>,
     *     total_rows: int
     * }
     *
     * @throws DomainException
     */
    public function read(string $filePath): array
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw new DomainException('File CSV tidak ditemukan atau tidak dapat dibaca.', 422);
        }

        $file = new SplFileObject($filePath, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl(',', '"', '\\');

        $headers = [];
        $rows = [];
        $rawRowIndex = 0;

        foreach ($file as $row) {
            $rawRowIndex++;

            if (! is_array($row) || empty($row)) {
                continue;
            }

            // Check if row is entirely empty
            $isAllEmpty = true;
            foreach ($row as $cell) {
                if ($cell !== null && trim((string) $cell) !== '') {
                    $isAllEmpty = false;
                    break;
                }
            }

            if ($isAllEmpty) {
                continue;
            }

            // First non-empty row is header
            if (empty($headers)) {
                $headers = $this->parseHeaders($row);

                continue;
            }

            // Data rows
            $dataRowNumber = count($rows) + 1;
            if ($dataRowNumber > self::MAX_ROWS) {
                throw new DomainException('Jumlah baris data melebihi batas maksimum 5.000 baris.', 422);
            }

            $mappedData = [];
            foreach ($headers as $index => $headerName) {
                $val = $row[$index] ?? null;
                $mappedData[$headerName] = ($val !== null && trim((string) $val) !== '') ? (string) $val : null;
            }

            $rows[] = [
                'row_number' => $rawRowIndex,
                'data' => $mappedData,
            ];
        }

        if (empty($headers)) {
            throw new DomainException('File CSV kosong atau tidak memiliki header.', 422);
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'total_rows' => count($rows),
        ];
    }

    /**
     * Normalize and parse header columns.
     *
     * @param  array<int, mixed>  $headerCells
     * @return array<int, string>
     *
     * @throws DomainException
     */
    private function parseHeaders(array $headerCells): array
    {
        $headers = [];

        foreach ($headerCells as $index => $cell) {
            $cellStr = (string) $cell;

            // Remove UTF-8 BOM on first column if present
            if ($index === 0) {
                $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
                if (str_starts_with($cellStr, $bom)) {
                    $cellStr = substr($cellStr, 3);
                }
            }

            $normalized = strtolower(trim($cellStr));

            if ($normalized === '') {
                throw new DomainException('Header CSV memiliki kolom kosong atau tidak valid.', 422);
            }

            if (in_array($normalized, $headers, true)) {
                throw new DomainException("Header CSV memiliki kolom duplikat: '{$normalized}'.", 422);
            }

            $headers[] = $normalized;
        }

        return $headers;
    }
}
