<?php

namespace App\Features\Reporting\Controllers;

use App\Features\Reporting\Requests\FieldBalanceReportRequest;
use App\Features\Reporting\Resources\FieldBalanceReportResource;
use App\Features\Reporting\Services\FieldBalanceReportQueryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class FieldBalanceReportController extends Controller
{
    public function __construct(
        private readonly FieldBalanceReportQueryService $queryService
    ) {}

    public function __invoke(FieldBalanceReportRequest $request): JsonResponse
    {
        $allowedLocationIds = $request->user()->getAllowedLocationIds();
        $report = $this->queryService->getReport($allowedLocationIds, $request->validated());

        return response()->json([
            'meta' => $report['meta'],
            'data' => FieldBalanceReportResource::collection($report['items']),
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
