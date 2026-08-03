<?php

namespace App\Features\Reporting\Services;

use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Reporting\Repositories\Contracts\ReportingRepositoryInterface;
use Carbon\CarbonImmutable;

class StockCardReportQueryService
{
    public function __construct(
        private readonly ReportingRepositoryInterface $repository
    ) {}

    public function getReport(array $allowedLocationIds, array $filters): array
    {
        $productId = (int) $filters['product_id'];
        $locationId = (int) $filters['location_id'];

        if (! in_array($locationId, $allowedLocationIds, true)) {
            abort(403, 'Akses ke lokasi ini ditolak.');
        }

        $startDate = CarbonImmutable::parse($filters['start_date'], 'Asia/Jakarta')->startOfDay();
        $endDate = CarbonImmutable::parse($filters['end_date'], 'Asia/Jakarta')->startOfDay();

        $startDateTime = $startDate->format('Y-m-d H:i:s');
        $endNextDayDateTime = $endDate->addDay()->format('Y-m-d H:i:s');

        $perPage = (int) ($filters['per_page'] ?? 15);

        $product = Product::with(['unit', 'category'])->findOrFail($productId);
        $location = Location::findOrFail($locationId);

        $openingBalance = $this->repository->getOpeningBalanceForStockCard(
            $productId,
            $locationId,
            $startDateTime
        );

        $movementsPaginator = $this->repository->getPaginatedStockCardMovements(
            $productId,
            $locationId,
            $startDateTime,
            $endNextDayDateTime,
            $perPage
        );

        $summary = $this->repository->getStockCardSummary(
            $productId,
            $locationId,
            $startDateTime,
            $endNextDayDateTime,
            $openingBalance
        );

        return [
            'meta' => [
                'product' => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'name' => $product->name,
                    'category_name' => $product->category?->name ?? '-',
                    'unit_name' => $product->unit?->name ?? '-',
                ],
                'location' => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'code' => $location->code,
                ],
                'start_date' => $filters['start_date'],
                'end_date' => $filters['end_date'],
                'date_basis' => 'POSTED_AT',
                'opening_balance' => $summary['opening_balance'],
                'closing_balance' => $summary['closing_balance'],
                'total_quantity_in' => $summary['total_quantity_in'],
                'total_quantity_out' => $summary['total_quantity_out'],
                'movement_count' => $summary['movement_count'],
            ],
            'movements' => $movementsPaginator,
        ];
    }
}
