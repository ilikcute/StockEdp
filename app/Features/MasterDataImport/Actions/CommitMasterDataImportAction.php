<?php

namespace App\Features\MasterDataImport\Actions;

use App\Features\Category\Actions\CreateCategoryAction;
use App\Features\Location\Actions\CreateLocationAction;
use App\Features\MasterDataImport\Contracts\MasterDataImportReaderInterface;
use App\Features\MasterDataImport\Enums\MasterDataImportType;
use App\Features\MasterDataImport\Services\MasterDataImportValidationService;
use App\Features\Product\Actions\CreateProductAction;
use App\Features\Unit\Actions\CreateUnitAction;
use App\Shared\Exceptions\DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommitMasterDataImportAction
{
    public function __construct(
        protected MasterDataImportReaderInterface $reader,
        protected MasterDataImportValidationService $validationService,
        protected CreateCategoryAction $createCategoryAction,
        protected CreateUnitAction $createUnitAction,
        protected CreateLocationAction $createLocationAction,
        protected CreateProductAction $createProductAction
    ) {}

    /**
     * Revalidate and import all rows in an atomic database transaction.
     *
     * @return array{
     *     type: string,
     *     total_rows: int,
     *     imported_rows: int,
     *     failed_rows: int
     * }
     *
     * @throws DomainException
     */
    public function execute(MasterDataImportType $type, UploadedFile $file, string $expectedSha256, int $userId): array
    {
        $realPath = $file->getRealPath();
        $actualSha256 = hash_file('sha256', $realPath);

        // 1. Checksum verification
        if (! hash_equals(strtolower($expectedSha256), strtolower($actualSha256))) {
            throw new DomainException('File telah berubah sejak proses validasi awal.', 409);
        }

        // 2. Re-parse
        $parsed = $this->reader->read($realPath);

        // 3. Re-validate headers
        $headerErrors = $this->validationService->validateHeaders($type, $parsed['headers']);
        if (! empty($headerErrors)) {
            throw new DomainException('Header file CSV tidak valid.', 422);
        }

        // 4. Re-validate rows
        $validationResult = $this->validationService->validate($type, $parsed['rows']);
        if ($validationResult['invalid_rows'] > 0 || ! empty($validationResult['errors'])) {
            throw new DomainException('Terdapat baris data yang tidak valid. Import hanya dapat diproses jika seluruh baris valid.', 422);
        }

        $normalizedRows = $validationResult['normalized_rows'];
        $totalRows = count($normalizedRows);

        // 5. Atomic Database Transaction Commit
        DB::transaction(function () use ($type, $normalizedRows, $userId): void {
            foreach ($normalizedRows as $row) {
                match ($type) {
                    MasterDataImportType::CATEGORIES => $this->createCategoryAction->execute([
                        'code' => $row['code'],
                        'name' => $row['name'],
                        'description' => $row['description'],
                    ]),
                    MasterDataImportType::UNITS => $this->createUnitAction->execute([
                        'code' => $row['code'],
                        'name' => $row['name'],
                        'symbol' => $row['symbol'],
                        'description' => $row['description'],
                    ]),
                    MasterDataImportType::LOCATIONS => $this->createLocationAction->execute([
                        'code' => $row['code'],
                        'name' => $row['name'],
                        'description' => $row['description'],
                        'address' => $row['address'],
                        'phone' => $row['phone'],
                    ], $userId),
                    MasterDataImportType::PRODUCTS => $this->createProductAction->execute([
                        'sku' => $row['sku'],
                        'barcode' => $row['barcode'],
                        'name' => $row['name'],
                        'description' => $row['description'],
                        'category_id' => $row['category_id'],
                        'unit_id' => $row['unit_id'],
                        'minimum_stock' => $row['minimum_stock'],
                    ], $userId),
                };
            }
        });

        Log::info('Master data bulk import successful', [
            'user_id' => $userId,
            'entity_type' => $type->value,
            'total_rows' => $totalRows,
            'imported_rows' => $totalRows,
        ]);

        return [
            'type' => $type->value,
            'total_rows' => $totalRows,
            'imported_rows' => $totalRows,
            'failed_rows' => 0,
        ];
    }
}
