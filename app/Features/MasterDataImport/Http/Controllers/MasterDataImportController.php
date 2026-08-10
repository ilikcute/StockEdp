<?php

namespace App\Features\MasterDataImport\Http\Controllers;

use App\Features\MasterDataImport\Actions\CommitMasterDataImportAction;
use App\Features\MasterDataImport\Actions\ValidateMasterDataImportAction;
use App\Features\MasterDataImport\Enums\MasterDataImportType;
use App\Features\MasterDataImport\Http\Requests\CommitMasterDataImportRequest;
use App\Features\MasterDataImport\Http\Requests\ValidateMasterDataImportRequest;
use App\Features\MasterDataImport\Services\MasterDataImportTemplateService;
use App\Shared\Exceptions\DomainException;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MasterDataImportController
{
    public function __construct(
        protected MasterDataImportTemplateService $templateService,
        protected ValidateMasterDataImportAction $validateAction,
        protected CommitMasterDataImportAction $commitAction
    ) {}

    /**
     * Download CSV template for master data import.
     */
    public function template(Request $request, string $type): Response
    {
        $importType = $this->resolveType($type);
        $this->authorizeType($request, $importType);

        $csvContent = $this->templateService->generateTemplate($importType);
        $filename = $importType->templateFilename();

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Validate CSV file and preview rows without DB mutation.
     */
    public function validate(ValidateMasterDataImportRequest $request, string $type): JsonResponse
    {
        $importType = $this->resolveType($type);
        $this->authorizeType($request, $importType);

        $result = $this->validateAction->execute($importType, $request->file('file'));

        return ApiResponse::success(
            data: $result,
            message: 'Validasi file import selesai.'
        );
    }

    /**
     * Revalidate and commit master data import within a transaction.
     */
    public function commit(CommitMasterDataImportRequest $request, string $type): JsonResponse
    {
        $importType = $this->resolveType($type);
        $this->authorizeType($request, $importType);

        $result = $this->commitAction->execute(
            type: $importType,
            file: $request->file('file'),
            expectedSha256: (string) $request->input('expected_sha256'),
            userId: (int) $request->user()->id
        );

        return ApiResponse::success(
            data: $result,
            message: "{$result['imported_rows']} {$importType->label()} berhasil diimport.",
            status: 201
        );
    }

    /**
     * Resolve string parameter to enum allowlist.
     *
     * @throws DomainException
     */
    private function resolveType(string $type): MasterDataImportType
    {
        $importType = MasterDataImportType::tryFrom(strtolower(trim($type)));

        if (! $importType) {
            throw new DomainException("Tipe import '{$type}' tidak didukung.", 422);
        }

        return $importType;
    }

    /**
     * Authorize that the current user has the required permission.
     */
    private function authorizeType(Request $request, MasterDataImportType $type): void
    {
        $user = $request->user();

        if (! $user || ! $user->can($type->requiredPermission()->value)) {
            abort(403, "Anda tidak memiliki izin untuk mengimpor {$type->label()}.");
        }
    }
}
