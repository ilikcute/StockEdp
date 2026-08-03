<?php

namespace App\Features\Reporting\Controllers;

use App\Features\Reporting\Requests\StockIssueReportRequest;
use App\Features\Reporting\Resources\StockIssueReportResource;
use App\Features\Reporting\Services\StockIssueReportQueryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StockIssueReportController extends Controller
{
    public function __construct(
        private readonly StockIssueReportQueryService $queryService
    ) {}

    public function __invoke(StockIssueReportRequest $request): JsonResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();
        $report = $this->queryService->getReport($allowedLocationIds, $request->validated());

        return response()->json([
            'meta' => $report['meta'],
            'data' => StockIssueReportResource::collection($report['items']),
            'pagination' => [
                'current_page' => $report['items']->currentPage(),
                'per_page' => $report['items']->perPage(),
                'total' => $report['items']->total(),
                'last_page' => $report['items']->lastPage(),
            ],
        ]);
    }
}
