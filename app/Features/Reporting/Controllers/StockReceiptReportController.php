<?php

namespace App\Features\Reporting\Controllers;

use App\Features\Reporting\Requests\StockReceiptReportRequest;
use App\Features\Reporting\Resources\StockReceiptReportResource;
use App\Features\Reporting\Services\StockReceiptReportQueryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StockReceiptReportController extends Controller
{
    public function __construct(
        private readonly StockReceiptReportQueryService $queryService
    ) {}

    public function __invoke(StockReceiptReportRequest $request): JsonResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();
        $report = $this->queryService->getReport($allowedLocationIds, $request->validated());

        return response()->json([
            'meta' => $report['meta'],
            'data' => StockReceiptReportResource::collection($report['items']),
            'pagination' => [
                'current_page' => $report['items']->currentPage(),
                'per_page' => $report['items']->perPage(),
                'total' => $report['items']->total(),
                'last_page' => $report['items']->lastPage(),
            ],
        ]);
    }
}
