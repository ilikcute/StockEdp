<?php

namespace App\Features\Replenishment\Http\Controllers;

use App\Features\Replenishment\DTOs\ReplenishmentFilterData;
use App\Features\Replenishment\Http\Requests\ReplenishmentFilterOptionsRequest;
use App\Features\Replenishment\Http\Requests\ReplenishmentRecommendationRequest;
use App\Features\Replenishment\Services\ReplenishmentRecommendationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ReplenishmentRecommendationController extends Controller
{
    public function __construct(
        private readonly ReplenishmentRecommendationService $service
    ) {}

    public function index(ReplenishmentRecommendationRequest $request): JsonResponse
    {
        $user = $request->user();
        $allowedLocationIds = $user ? $user->getAllowedLocationIds() : [];

        $filters = ReplenishmentFilterData::fromArray($request->validated());

        $data = $this->service->getRecommendations($allowedLocationIds, $filters);

        return response()->api(
            $data,
            'Rekomendasi reorder berhasil dimuat.'
        );
    }

    public function filterOptions(ReplenishmentFilterOptionsRequest $request): JsonResponse
    {
        $user = $request->user();
        $allowedLocationIds = $user ? $user->getAllowedLocationIds() : [];

        $options = $this->service->getFilterOptions($allowedLocationIds);

        return response()->api(
            $options,
            'Opsi filter rekomendasi reorder berhasil dimuat.'
        );
    }
}
