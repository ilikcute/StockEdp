<?php

namespace App\Features\MasterDataImport\Services;

use App\Features\Category\Models\Category;
use App\Features\Location\Models\Location;
use App\Features\MasterDataImport\Enums\MasterDataImportType;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;

class MasterDataImportValidationService
{
    public const PREVIEW_LIMIT = 20;

    /**
     * Validate headers against canonical entity headers.
     *
     * @param  array<int, string>  $parsedHeaders
     * @return array<int, array{row: int, field: string, code: string, message: string}>
     */
    public function validateHeaders(MasterDataImportType $type, array $parsedHeaders): array
    {
        $canonical = $type->canonicalHeaders();
        $errors = [];

        $missing = array_diff($canonical, $parsedHeaders);
        foreach ($missing as $missingField) {
            $errors[] = [
                'row' => 1,
                'field' => $missingField,
                'code' => 'MISSING_REQUIRED_HEADER',
                'message' => "Kolom wajib '{$missingField}' tidak ditemukan pada header file CSV.",
            ];
        }

        $unknown = array_diff($parsedHeaders, $canonical);
        foreach ($unknown as $unknownField) {
            $errors[] = [
                'row' => 1,
                'field' => $unknownField,
                'code' => 'UNKNOWN_HEADER',
                'message' => "Kolom '{$unknownField}' tidak dikenali dalam template {$type->label()}.",
            ];
        }

        return $errors;
    }

    /**
     * Validate all rows for the given import type.
     *
     * @param  array<int, array{row_number: int, data: array<string, ?string>}>  $rows
     * @return array{
     *     total_rows: int,
     *     valid_rows: int,
     *     invalid_rows: int,
     *     preview: array<int, array<string, mixed>>,
     *     errors: array<int, array{row: int, field: string, code: string, message: string}>,
     *     normalized_rows: array<int, array<string, mixed>>
     * }
     */
    public function validate(MasterDataImportType $type, array $rows): array
    {
        return match ($type) {
            MasterDataImportType::CATEGORIES => $this->validateCategories($rows),
            MasterDataImportType::UNITS => $this->validateUnits($rows),
            MasterDataImportType::LOCATIONS => $this->validateLocations($rows),
            MasterDataImportType::PRODUCTS => $this->validateProducts($rows),
        };
    }

    /**
     * Validate Categories rows.
     */
    private function validateCategories(array $rows): array
    {
        $errors = [];
        $normalizedRows = [];
        $seenCodesInFile = [];
        $allCodes = [];

        // 1. Collect codes for DB batch lookup
        foreach ($rows as $item) {
            $code = isset($item['data']['code']) ? strtoupper(trim((string) $item['data']['code'])) : '';
            if ($code !== '') {
                $allCodes[] = $code;
            }
        }

        $existingDbCodes = ! empty($allCodes)
            ? Category::whereIn('code', array_unique($allCodes))->pluck('code')->all()
            : [];
        $existingDbCodesMap = array_fill_keys(array_map('strtoupper', $existingDbCodes), true);

        // 2. Validate row by row
        foreach ($rows as $item) {
            $rowNum = $item['row_number'];
            $data = $item['data'];
            $rowErrors = [];

            $rawCode = $data['code'] ?? null;
            $rawName = $data['name'] ?? null;
            $rawDesc = $data['description'] ?? null;

            $code = $rawCode !== null ? strtoupper(trim((string) $rawCode)) : '';
            $name = $rawName !== null ? trim((string) $rawName) : '';
            $desc = $rawDesc !== null ? trim((string) $rawDesc) : null;

            if ($code === '') {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'code',
                    'code' => 'REQUIRED_FIELD_MISSING',
                    'message' => 'Kode kategori wajib diisi.',
                ];
            } elseif (strlen($code) > 50) {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'code',
                    'code' => 'FIELD_TOO_LONG',
                    'message' => 'Kode kategori maksimal 50 karakter.',
                ];
            } else {
                if (isset($seenCodesInFile[$code])) {
                    $rowErrors[] = [
                        'row' => $rowNum,
                        'field' => 'code',
                        'code' => 'DUPLICATE_CODE_IN_FILE',
                        'message' => "Kode kategori '{$code}' duplikat dalam file CSV (sebelumnya pada baris {$seenCodesInFile[$code]}).",
                    ];
                } else {
                    $seenCodesInFile[$code] = $rowNum;
                }

                if (isset($existingDbCodesMap[$code])) {
                    $rowErrors[] = [
                        'row' => $rowNum,
                        'field' => 'code',
                        'code' => 'DUPLICATE_CODE_IN_DB',
                        'message' => "Kode kategori '{$code}' sudah ada di database.",
                    ];
                }
            }

            if ($name === '') {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'name',
                    'code' => 'REQUIRED_FIELD_MISSING',
                    'message' => 'Nama kategori wajib diisi.',
                ];
            } elseif (strlen($name) > 100) {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'name',
                    'code' => 'FIELD_TOO_LONG',
                    'message' => 'Nama kategori maksimal 100 karakter.',
                ];
            }

            $normalizedRow = [
                'row_number' => $rowNum,
                'code' => $code,
                'name' => $name,
                'description' => $desc !== '' ? $desc : null,
                'is_valid' => empty($rowErrors),
                'row_errors' => $rowErrors,
            ];

            $normalizedRows[] = $normalizedRow;
            foreach ($rowErrors as $err) {
                $errors[] = $err;
            }
        }

        return $this->formatResult($normalizedRows, $errors);
    }

    /**
     * Validate Units rows.
     */
    private function validateUnits(array $rows): array
    {
        $errors = [];
        $normalizedRows = [];
        $seenCodesInFile = [];
        $allCodes = [];

        foreach ($rows as $item) {
            $code = isset($item['data']['code']) ? strtoupper(trim((string) $item['data']['code'])) : '';
            if ($code !== '') {
                $allCodes[] = $code;
            }
        }

        $existingDbCodes = ! empty($allCodes)
            ? Unit::whereIn('code', array_unique($allCodes))->pluck('code')->all()
            : [];
        $existingDbCodesMap = array_fill_keys(array_map('strtoupper', $existingDbCodes), true);

        foreach ($rows as $item) {
            $rowNum = $item['row_number'];
            $data = $item['data'];
            $rowErrors = [];

            $code = isset($data['code']) ? strtoupper(trim((string) $data['code'])) : '';
            $name = isset($data['name']) ? trim((string) $data['name']) : '';
            $symbol = isset($data['symbol']) ? trim((string) $data['symbol']) : '';
            $desc = isset($data['description']) ? trim((string) $data['description']) : null;

            if ($code === '') {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'code',
                    'code' => 'REQUIRED_FIELD_MISSING',
                    'message' => 'Kode satuan wajib diisi.',
                ];
            } elseif (strlen($code) > 50) {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'code',
                    'code' => 'FIELD_TOO_LONG',
                    'message' => 'Kode satuan maksimal 50 karakter.',
                ];
            } else {
                if (isset($seenCodesInFile[$code])) {
                    $rowErrors[] = [
                        'row' => $rowNum,
                        'field' => 'code',
                        'code' => 'DUPLICATE_CODE_IN_FILE',
                        'message' => "Kode satuan '{$code}' duplikat dalam file CSV (sebelumnya pada baris {$seenCodesInFile[$code]}).",
                    ];
                } else {
                    $seenCodesInFile[$code] = $rowNum;
                }

                if (isset($existingDbCodesMap[$code])) {
                    $rowErrors[] = [
                        'row' => $rowNum,
                        'field' => 'code',
                        'code' => 'DUPLICATE_CODE_IN_DB',
                        'message' => "Kode satuan '{$code}' sudah ada di database.",
                    ];
                }
            }

            if ($name === '') {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'name',
                    'code' => 'REQUIRED_FIELD_MISSING',
                    'message' => 'Nama satuan wajib diisi.',
                ];
            } elseif (strlen($name) > 100) {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'name',
                    'code' => 'FIELD_TOO_LONG',
                    'message' => 'Nama satuan maksimal 100 karakter.',
                ];
            }

            if ($symbol === '') {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'symbol',
                    'code' => 'REQUIRED_FIELD_MISSING',
                    'message' => 'Simbol satuan wajib diisi.',
                ];
            } elseif (strlen($symbol) > 20) {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'symbol',
                    'code' => 'FIELD_TOO_LONG',
                    'message' => 'Simbol satuan maksimal 20 karakter.',
                ];
            }

            $normalizedRow = [
                'row_number' => $rowNum,
                'code' => $code,
                'name' => $name,
                'symbol' => $symbol,
                'description' => $desc !== '' ? $desc : null,
                'is_valid' => empty($rowErrors),
                'row_errors' => $rowErrors,
            ];

            $normalizedRows[] = $normalizedRow;
            foreach ($rowErrors as $err) {
                $errors[] = $err;
            }
        }

        return $this->formatResult($normalizedRows, $errors);
    }

    /**
     * Validate Locations rows.
     */
    private function validateLocations(array $rows): array
    {
        $errors = [];
        $normalizedRows = [];
        $seenCodesInFile = [];
        $allCodes = [];

        foreach ($rows as $item) {
            $code = isset($item['data']['code']) ? strtoupper(trim((string) $item['data']['code'])) : '';
            if ($code !== '') {
                $allCodes[] = $code;
            }
        }

        $existingDbCodes = ! empty($allCodes)
            ? Location::whereIn('code', array_unique($allCodes))->pluck('code')->all()
            : [];
        $existingDbCodesMap = array_fill_keys(array_map('strtoupper', $existingDbCodes), true);

        foreach ($rows as $item) {
            $rowNum = $item['row_number'];
            $data = $item['data'];
            $rowErrors = [];

            $code = isset($data['code']) ? strtoupper(trim((string) $data['code'])) : '';
            $name = isset($data['name']) ? trim((string) $data['name']) : '';
            $desc = isset($data['description']) ? trim((string) $data['description']) : null;
            $addr = isset($data['address']) ? trim((string) $data['address']) : null;
            $phone = isset($data['phone']) ? trim((string) $data['phone']) : null;

            if ($code === '') {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'code',
                    'code' => 'REQUIRED_FIELD_MISSING',
                    'message' => 'Kode lokasi wajib diisi.',
                ];
            } elseif (strlen($code) > 50) {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'code',
                    'code' => 'FIELD_TOO_LONG',
                    'message' => 'Kode lokasi maksimal 50 karakter.',
                ];
            } else {
                if (isset($seenCodesInFile[$code])) {
                    $rowErrors[] = [
                        'row' => $rowNum,
                        'field' => 'code',
                        'code' => 'DUPLICATE_CODE_IN_FILE',
                        'message' => "Kode lokasi '{$code}' duplikat dalam file CSV (sebelumnya pada baris {$seenCodesInFile[$code]}).",
                    ];
                } else {
                    $seenCodesInFile[$code] = $rowNum;
                }

                if (isset($existingDbCodesMap[$code])) {
                    $rowErrors[] = [
                        'row' => $rowNum,
                        'field' => 'code',
                        'code' => 'DUPLICATE_CODE_IN_DB',
                        'message' => "Kode lokasi '{$code}' sudah ada di database.",
                    ];
                }
            }

            if ($name === '') {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'name',
                    'code' => 'REQUIRED_FIELD_MISSING',
                    'message' => 'Nama lokasi wajib diisi.',
                ];
            } elseif (strlen($name) > 100) {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'name',
                    'code' => 'FIELD_TOO_LONG',
                    'message' => 'Nama lokasi maksimal 100 karakter.',
                ];
            }

            if ($phone !== null && strlen($phone) > 50) {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'phone',
                    'code' => 'FIELD_TOO_LONG',
                    'message' => 'Nomor telepon lokasi maksimal 50 karakter.',
                ];
            }

            $normalizedRow = [
                'row_number' => $rowNum,
                'code' => $code,
                'name' => $name,
                'description' => $desc !== '' ? $desc : null,
                'address' => $addr !== '' ? $addr : null,
                'phone' => $phone !== '' ? $phone : null,
                'is_valid' => empty($rowErrors),
                'row_errors' => $rowErrors,
            ];

            $normalizedRows[] = $normalizedRow;
            foreach ($rowErrors as $err) {
                $errors[] = $err;
            }
        }

        return $this->formatResult($normalizedRows, $errors);
    }

    /**
     * Validate Products rows.
     */
    private function validateProducts(array $rows): array
    {
        $errors = [];
        $normalizedRows = [];
        $seenSkusInFile = [];
        $seenBarcodesInFile = [];

        $allSkus = [];
        $allBarcodes = [];
        $allCategoryCodes = [];
        $allUnitCodes = [];

        foreach ($rows as $item) {
            $sku = isset($item['data']['sku']) ? strtoupper(trim((string) $item['data']['sku'])) : '';
            if ($sku !== '') {
                $allSkus[] = $sku;
            }

            $barcode = isset($item['data']['barcode']) ? trim((string) $item['data']['barcode']) : '';
            if ($barcode !== '') {
                $allBarcodes[] = $barcode;
            }

            $catCode = isset($item['data']['category_code']) ? strtoupper(trim((string) $item['data']['category_code'])) : '';
            if ($catCode !== '') {
                $allCategoryCodes[] = $catCode;
            }

            $unitCode = isset($item['data']['unit_code']) ? strtoupper(trim((string) $item['data']['unit_code'])) : '';
            if ($unitCode !== '') {
                $allUnitCodes[] = $unitCode;
            }
        }

        // Batch preloads
        $existingDbSkus = ! empty($allSkus)
            ? Product::whereIn('sku', array_unique($allSkus))->pluck('sku')->all()
            : [];
        $existingDbSkusMap = array_fill_keys(array_map('strtoupper', $existingDbSkus), true);

        $existingDbBarcodes = ! empty($allBarcodes)
            ? Product::whereNotNull('barcode')->whereIn('barcode', array_unique($allBarcodes))->pluck('barcode')->all()
            : [];
        $existingDbBarcodesMap = array_fill_keys($existingDbBarcodes, true);

        $categoryMap = ! empty($allCategoryCodes)
            ? Category::whereIn('code', array_unique($allCategoryCodes))->pluck('id', 'code')->all()
            : [];
        $categoryMap = array_change_key_case($categoryMap, CASE_UPPER);

        $unitMap = ! empty($allUnitCodes)
            ? Unit::whereIn('code', array_unique($allUnitCodes))->pluck('id', 'code')->all()
            : [];
        $unitMap = array_change_key_case($unitMap, CASE_UPPER);

        foreach ($rows as $item) {
            $rowNum = $item['row_number'];
            $data = $item['data'];
            $rowErrors = [];

            $sku = isset($data['sku']) ? strtoupper(trim((string) $data['sku'])) : '';
            $barcode = isset($data['barcode']) ? trim((string) $data['barcode']) : null;
            $name = isset($data['name']) ? trim((string) $data['name']) : '';
            $desc = isset($data['description']) ? trim((string) $data['description']) : null;
            $catCode = isset($data['category_code']) ? strtoupper(trim((string) $data['category_code'])) : '';
            $unitCode = isset($data['unit_code']) ? strtoupper(trim((string) $data['unit_code'])) : '';
            $minStockRaw = isset($data['minimum_stock']) ? trim((string) $data['minimum_stock']) : null;

            // 1. SKU validation
            if ($sku === '') {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'sku',
                    'code' => 'REQUIRED_FIELD_MISSING',
                    'message' => 'SKU produk wajib diisi.',
                ];
            } elseif (strlen($sku) > 100) {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'sku',
                    'code' => 'FIELD_TOO_LONG',
                    'message' => 'SKU produk maksimal 100 karakter.',
                ];
            } else {
                if (isset($seenSkusInFile[$sku])) {
                    $rowErrors[] = [
                        'row' => $rowNum,
                        'field' => 'sku',
                        'code' => 'DUPLICATE_SKU_IN_FILE',
                        'message' => "SKU '{$sku}' duplikat dalam file CSV (sebelumnya pada baris {$seenSkusInFile[$sku]}).",
                    ];
                } else {
                    $seenSkusInFile[$sku] = $rowNum;
                }

                if (isset($existingDbSkusMap[$sku])) {
                    $rowErrors[] = [
                        'row' => $rowNum,
                        'field' => 'sku',
                        'code' => 'DUPLICATE_SKU_IN_DB',
                        'message' => "SKU '{$sku}' sudah ada di database.",
                    ];
                }
            }

            // 2. Barcode validation
            $normalizedBarcode = ($barcode !== null && $barcode !== '') ? $barcode : null;
            if ($normalizedBarcode !== null) {
                if (strlen($normalizedBarcode) > 100) {
                    $rowErrors[] = [
                        'row' => $rowNum,
                        'field' => 'barcode',
                        'code' => 'FIELD_TOO_LONG',
                        'message' => 'Barcode produk maksimal 100 karakter.',
                    ];
                } else {
                    if (isset($seenBarcodesInFile[$normalizedBarcode])) {
                        $rowErrors[] = [
                            'row' => $rowNum,
                            'field' => 'barcode',
                            'code' => 'DUPLICATE_BARCODE_IN_FILE',
                            'message' => "Barcode '{$normalizedBarcode}' duplikat dalam file CSV (sebelumnya pada baris {$seenBarcodesInFile[$normalizedBarcode]}).",
                        ];
                    } else {
                        $seenBarcodesInFile[$normalizedBarcode] = $rowNum;
                    }

                    if (isset($existingDbBarcodesMap[$normalizedBarcode])) {
                        $rowErrors[] = [
                            'row' => $rowNum,
                            'field' => 'barcode',
                            'code' => 'DUPLICATE_BARCODE_IN_DB',
                            'message' => "Barcode '{$normalizedBarcode}' sudah ada di database.",
                        ];
                    }
                }
            }

            // 3. Name validation
            if ($name === '') {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'name',
                    'code' => 'REQUIRED_FIELD_MISSING',
                    'message' => 'Nama produk wajib diisi.',
                ];
            } elseif (strlen($name) > 255) {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'name',
                    'code' => 'FIELD_TOO_LONG',
                    'message' => 'Nama produk maksimal 255 karakter.',
                ];
            }

            // 4. Category reference validation
            $categoryId = null;
            if ($catCode === '') {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'category_code',
                    'code' => 'REQUIRED_FIELD_MISSING',
                    'message' => 'Kode kategori wajib diisi.',
                ];
            } elseif (! isset($categoryMap[$catCode])) {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'category_code',
                    'code' => 'CATEGORY_NOT_FOUND',
                    'message' => "Kategori dengan kode '{$catCode}' tidak ditemukan.",
                ];
            } else {
                $categoryId = (int) $categoryMap[$catCode];
            }

            // 5. Unit reference validation
            $unitId = null;
            if ($unitCode === '') {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'unit_code',
                    'code' => 'REQUIRED_FIELD_MISSING',
                    'message' => 'Kode satuan wajib diisi.',
                ];
            } elseif (! isset($unitMap[$unitCode])) {
                $rowErrors[] = [
                    'row' => $rowNum,
                    'field' => 'unit_code',
                    'code' => 'UNIT_NOT_FOUND',
                    'message' => "Satuan dengan kode '{$unitCode}' tidak ditemukan.",
                ];
            } else {
                $unitId = (int) $unitMap[$unitCode];
            }

            // 6. Minimum Stock decimal validation
            $minStock = '0.0000';
            if ($minStockRaw !== null && $minStockRaw !== '') {
                if (! preg_match('/^\d+(\.\d{1,4})?$/', $minStockRaw)) {
                    $rowErrors[] = [
                        'row' => $rowNum,
                        'field' => 'minimum_stock',
                        'code' => 'INVALID_MINIMUM_STOCK',
                        'message' => 'Stok minimum harus berupa angka desimal non-negatif maksimal 4 digit di belakang koma.',
                    ];
                } else {
                    $minStock = number_format((float) $minStockRaw, 4, '.', '');
                }
            }

            $normalizedRow = [
                'row_number' => $rowNum,
                'sku' => $sku,
                'barcode' => $normalizedBarcode,
                'name' => $name,
                'description' => $desc !== '' ? $desc : null,
                'category_code' => $catCode,
                'unit_code' => $unitCode,
                'category_id' => $categoryId,
                'unit_id' => $unitId,
                'minimum_stock' => $minStock,
                'is_valid' => empty($rowErrors),
                'row_errors' => $rowErrors,
            ];

            $normalizedRows[] = $normalizedRow;
            foreach ($rowErrors as $err) {
                $errors[] = $err;
            }
        }

        return $this->formatResult($normalizedRows, $errors);
    }

    /**
     * Format the overall validation result.
     *
     * @param  array<int, array<string, mixed>>  $normalizedRows
     * @param  array<int, array{row: int, field: string, code: string, message: string}>  $errors
     * @return array{
     *     total_rows: int,
     *     valid_rows: int,
     *     invalid_rows: int,
     *     preview: array<int, array<string, mixed>>,
     *     errors: array<int, array{row: int, field: string, code: string, message: string}>,
     *     normalized_rows: array<int, array<string, mixed>>
     * }
     */
    private function formatResult(array $normalizedRows, array $errors): array
    {
        $totalRows = count($normalizedRows);
        $validRows = 0;
        $invalidRows = 0;

        foreach ($normalizedRows as $row) {
            if ($row['is_valid']) {
                $validRows++;
            } else {
                $invalidRows++;
            }
        }

        $preview = array_slice($normalizedRows, 0, self::PREVIEW_LIMIT);

        return [
            'total_rows' => $totalRows,
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'preview' => $preview,
            'errors' => $errors,
            'normalized_rows' => $normalizedRows,
        ];
    }
}
