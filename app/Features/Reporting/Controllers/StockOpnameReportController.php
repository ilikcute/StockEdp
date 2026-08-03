<?php

namespace App\Features\Reporting\Controllers;

use App\Features\Reporting\Requests\StockOpnameReportRequest;
use App\Features\Reporting\Resources\StockOpnameReportResource;
use App\Features\Reporting\Services\StockOpnameReportQueryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StockOpnameReportController extends Controller
{
    public function __construct(
        private readonly StockOpnameReportQueryService $queryService
    ) {}

    public function __invoke(StockOpnameReportRequest $request): JsonResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();
        $report = $this->queryService->getReport($allowedLocationIds, $request->validated());

        return response()->json([
            'meta' => $report['meta'],
            'data' => StockOpnameReportResource::collection($report['items']),
            'pagination' => [
                'current_page' => $report['items']->currentPage(),
                'per_page' => $report['items']->perPage(),
                'total' => $report['items']->total(),
                'last_page' => $report['items']->lastPage(),
            ],
        ]);
    }
}
