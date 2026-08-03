<?php

namespace App\Features\Reporting\Controllers;

use App\Features\Reporting\Requests\StockAdjustmentReportRequest;
use App\Features\Reporting\Resources\StockAdjustmentReportResource;
use App\Features\Reporting\Services\StockAdjustmentReportQueryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StockAdjustmentReportController extends Controller
{
    public function __construct(
        private readonly StockAdjustmentReportQueryService $queryService
    ) {}

    public function __invoke(StockAdjustmentReportRequest $request): JsonResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();
        $report = $this->queryService->getReport($allowedLocationIds, $request->validated());

        return response()->json([
            'meta' => $report['meta'],
            'data' => StockAdjustmentReportResource::collection($report['items']),
            'pagination' => [
                'current_page' => $report['items']->currentPage(),
                'per_page' => $report['items']->perPage(),
                'total' => $report['items']->total(),
                'last_page' => $report['items']->lastPage(),
            ],
        ]);
    }
}
