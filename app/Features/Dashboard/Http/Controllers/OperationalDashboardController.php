<?php

namespace App\Features\Dashboard\Http\Controllers;

use App\Features\Dashboard\Http\Requests\DashboardFilterRequest;
use App\Features\Dashboard\Http\Resources\OperationalDashboardResource;
use App\Features\Dashboard\Services\OperationalDashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OperationalDashboardController extends Controller
{
    public function __construct(
        private readonly OperationalDashboardService $service
    ) {}

    public function index(DashboardFilterRequest $request): JsonResponse
    {
        $user = $request->user();
        $allowedLocationIds = $user ? $user->getAllowedLocationIds() : [];

        $data = $this->service->getDashboardData(
            $allowedLocationIds,
            $request->validated()
        );

        return response()->api(
            (new OperationalDashboardResource($data))->resolve(),
            'Dashboard operational data loaded successfully.'
        );
    }

    public function movementSummary(DashboardFilterRequest $request): JsonResponse
    {
        $user = $request->user();
        $allowedLocationIds = $user ? $user->getAllowedLocationIds() : [];

        $data = $this->service->getMovementSummary(
            $allowedLocationIds,
            $request->validated()
        );

        return response()->api(
            $data,
            'Ringkasan pergerakan persediaan berhasil dimuat.'
        );
    }
}
