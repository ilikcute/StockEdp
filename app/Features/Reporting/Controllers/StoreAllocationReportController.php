<?php

namespace App\Features\Reporting\Controllers;

use App\Features\Reporting\Requests\StoreAllocationReportRequest;
use App\Features\Reporting\Resources\StoreAllocationReportResource;
use App\Features\Reporting\Services\StoreAllocationReportQueryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StoreAllocationReportController extends Controller
{
    public function __construct(
        private readonly StoreAllocationReportQueryService $queryService
    ) {}

    public function __invoke(StoreAllocationReportRequest $request): JsonResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();
        $report = $this->queryService->getReport($allowedLocationIds, $request->validated());

        return response()->json([
            'meta' => $report['meta'],
            'data' => StoreAllocationReportResource::collection($report['items']),
            'pagination' => [
                'current_page' => $report['items']->currentPage(),
                'per_page' => $report['items']->perPage(),
                'total' => $report['items']->total(),
                'last_page' => $report['items']->lastPage(),
                'from' => $report['items']->firstItem(),
                'to' => $report['items']->lastItem(),
            ],
        ]);
    }
}
