<?php

namespace App\Features\MasterDataImport\Contracts;

interface MasterDataImportReaderInterface
{
    /**
     * Parse file and return headers and raw rows.
     *
     * @return array{
     *     headers: array<int, string>,
     *     rows: array<int, array<string, mixed>>,
     *     total_rows: int
     * }
     */
    public function read(string $filePath): array;
}
