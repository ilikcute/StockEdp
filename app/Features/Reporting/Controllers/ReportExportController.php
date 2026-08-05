<?php

namespace App\Features\Reporting\Controllers;

use App\Features\Reporting\Requests\InventoryBalanceReportRequest;
use App\Features\Reporting\Requests\LowStockReportRequest;
use App\Features\Reporting\Requests\StockAdjustmentReportRequest;
use App\Features\Reporting\Requests\StockCardReportRequest;
use App\Features\Reporting\Requests\StockIssueReportRequest;
use App\Features\Reporting\Requests\StockOpnameReportRequest;
use App\Features\Reporting\Requests\StockReceiptReportRequest;
use App\Features\Reporting\Requests\StockTransferReportRequest;
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

        return $this->exportService->exportBalances($allowedLocationIds, $request->validated());
    }

    public function lowStock(LowStockReportRequest $request): StreamedResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();

        return $this->exportService->exportLowStock($allowedLocationIds, $request->validated());
    }

    public function stockCard(StockCardReportRequest $request): StreamedResponse
    {
        return $this->exportService->exportStockCard($request->validated());
    }

    public function stockReceipts(StockReceiptReportRequest $request): StreamedResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();

        return $this->exportService->exportStockReceipts($allowedLocationIds, $request->validated());
    }

    public function stockIssues(StockIssueReportRequest $request): StreamedResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();

        return $this->exportService->exportStockIssues($allowedLocationIds, $request->validated());
    }

    public function stockTransfers(StockTransferReportRequest $request): StreamedResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();

        return $this->exportService->exportStockTransfers($allowedLocationIds, $request->validated());
    }

    public function stockAdjustments(StockAdjustmentReportRequest $request): StreamedResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();

        return $this->exportService->exportStockAdjustments($allowedLocationIds, $request->validated());
    }

    public function stockOpnames(StockOpnameReportRequest $request): StreamedResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();

        return $this->exportService->exportStockOpnames($allowedLocationIds, $request->validated());
    }
}
