<?php

namespace App\Features\MasterDataImport\Actions;

use App\Features\MasterDataImport\Contracts\MasterDataImportReaderInterface;
use App\Features\MasterDataImport\Enums\MasterDataImportType;
use App\Features\MasterDataImport\Services\MasterDataImportValidationService;
use App\Shared\Exceptions\DomainException;
use Illuminate\Http\UploadedFile;

class ValidateMasterDataImportAction
{
    public function __construct(
        protected MasterDataImportReaderInterface $reader,
        protected MasterDataImportValidationService $validationService
    ) {}

    /**
     * Parse and validate uploaded CSV file without database mutation.
     *
     * @return array{
     *     type: string,
     *     file_name: string,
     *     sha256: string,
     *     total_rows: int,
     *     valid_rows: int,
     *     invalid_rows: int,
     *     preview: array<int, array<string, mixed>>,
     *     errors: array<int, array{row: int, field: string, code: string, message: string}>
     * }
     *
     * @throws DomainException
     */
    public function execute(MasterDataImportType $type, UploadedFile $file): array
    {
        $realPath = $file->getRealPath();
        $fileName = $file->getClientOriginalName();
        $sha256 = hash_file('sha256', $realPath);

        $parsed = $this->reader->read($realPath);

        // 1. Header validation
        $headerErrors = $this->validationService->validateHeaders($type, $parsed['headers']);
        if (! empty($headerErrors)) {
            return [
                'type' => $type->value,
                'file_name' => $fileName,
                'sha256' => $sha256,
                'total_rows' => $parsed['total_rows'],
                'valid_rows' => 0,
                'invalid_rows' => $parsed['total_rows'],
                'preview' => [],
                'errors' => $headerErrors,
            ];
        }

        // 2. Row data validation
        $result = $this->validationService->validate($type, $parsed['rows']);

        return [
            'type' => $type->value,
            'file_name' => $fileName,
            'sha256' => $sha256,
            'total_rows' => $result['total_rows'],
            'valid_rows' => $result['valid_rows'],
            'invalid_rows' => $result['invalid_rows'],
            'preview' => $result['preview'],
            'errors' => $result['errors'],
        ];
    }
}
