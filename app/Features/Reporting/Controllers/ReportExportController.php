<?php

namespace App\Features\Reporting\Controllers;

use App\Features\Reporting\Requests\FieldBalanceReportRequest;
use App\Features\Reporting\Requests\InventoryBalanceReportRequest;
use App\Features\Reporting\Requests\InventoryMovementReportRequest;
use App\Features\Reporting\Requests\LowStockReportRequest;
use App\Features\Reporting\Requests\StockAdjustmentReportRequest;
use App\Features\Reporting\Requests\StockCardReportRequest;
use App\Features\Reporting\Requests\StockIssueReportRequest;
use App\Features\Reporting\Requests\StockOpnameReportRequest;
use App\Features\Reporting\Requests\StockReceiptReportRequest;
use App\Features\Reporting\Requests\StockTransferReportRequest;
use App\Features\Reporting\Requests\StoreAllocationReportRequest;
use App\Features\Reporting\Services\ReportExportService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function __construct(
        private readonly ReportExportService $exportService
    ) {}

    public function inventoryBalances(InventoryBalanceReportRequest $request): StreamedResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();
        $format = (string) ($request->validated('format') ?? $request->input('format', 'csv'));

        return $this->exportService->exportBalances($allowedLocationIds, $request->validated(), $format);
    }

    public function lowStock(LowStockReportRequest $request): StreamedResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();
        $format = (string) ($request->validated('format') ?? $request->input('format', 'csv'));

        return $this->exportService->exportLowStock($allowedLocationIds, $request->validated(), $format);
    }

    public function inventoryMovement(InventoryMovementReportRequest $request): StreamedResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();
        $format = (string) ($request->validated('format') ?? $request->input('format', 'csv'));

        return $this->exportService->exportInventoryMovement($allowedLocationIds, $request->validated(), $format);
    }

    public function stockCard(StockCardReportRequest $request): StreamedResponse
    {
        $allowedLocationIds = $request->user()
            ? $request->user()->getAllowedLocationIds()
            : [];
        $format = (string) ($request->validated('format') ?? $request->input('format', 'csv'));

        return $this->exportService->exportStockCard(
            $allowedLocationIds,
            $request->validated(),
            $format
        );
    }

    public function stockReceipts(StockReceiptReportRequest $request): StreamedResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();
        $format = (string) ($request->validated('format') ?? $request->input('format', 'csv'));

        return $this->exportService->exportStockReceipts($allowedLocationIds, $request->validated(), $format);
    }

    public function stockIssues(StockIssueReportRequest $request): StreamedResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();
        $format = (string) ($request->validated('format') ?? $request->input('format', 'csv'));

        return $this->exportService->exportStockIssues($allowedLocationIds, $request->validated(), $format);
    }

    public function stockTransfers(StockTransferReportRequest $request): StreamedResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();
        $format = (string) ($request->validated('format') ?? $request->input('format', 'csv'));

        return $this->exportService->exportStockTransfers($allowedLocationIds, $request->validated(), $format);
    }

    public function stockAdjustments(StockAdjustmentReportRequest $request): StreamedResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();
        $format = (string) ($request->validated('format') ?? $request->input('format', 'csv'));

        return $this->exportService->exportStockAdjustments($allowedLocationIds, $request->validated(), $format);
    }

    public function stockOpnames(StockOpnameReportRequest $request): StreamedResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();
        $format = (string) ($request->validated('format') ?? $request->input('format', 'csv'));

        return $this->exportService->exportStockOpnames($allowedLocationIds, $request->validated(), $format);
    }

    public function storeAllocations(StoreAllocationReportRequest $request): StreamedResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();
        $format = (string) ($request->validated('format') ?? $request->input('format', 'csv'));

        return $this->exportService->exportStoreAllocations($allowedLocationIds, $request->validated(), $format);
    }

    public function fieldBalances(FieldBalanceReportRequest $request): StreamedResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();
        $format = (string) ($request->validated('format') ?? $request->input('format', 'csv'));

        return $this->exportService->exportFieldBalances($allowedLocationIds, $request->validated(), $format);
    }
}
