<?php

namespace App\Features\Reporting\Controllers;

use App\Features\Reporting\Requests\StockTransferReportRequest;
use App\Features\Reporting\Resources\StockTransferReportResource;
use App\Features\Reporting\Services\StockTransferReportQueryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StockTransferReportController extends Controller
{
    public function __construct(
        private readonly StockTransferReportQueryService $queryService
    ) {}

    public function __invoke(StockTransferReportRequest $request): JsonResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();
        $report = $this->queryService->getReport($allowedLocationIds, $request->validated());

        return response()->json([
            'meta' => $report['meta'],
            'data' => StockTransferReportResource::collection($report['items']),
            'pagination' => [
                'current_page' => $report['items']->currentPage(),
                'per_page' => $report['items']->perPage(),
                'total' => $report['items']->total(),
                'last_page' => $report['items']->lastPage(),
            ],
        ]);
    }
}
