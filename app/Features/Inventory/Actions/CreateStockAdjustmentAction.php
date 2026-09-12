<?php

namespace App\Features\Inventory\Actions;

use App\Features\Inventory\Enums\AdjustmentReason;
use App\Features\Inventory\Enums\AdjustmentStatus;
use App\Features\Inventory\Models\StockAdjustment;
use App\Features\Inventory\Repositories\Contracts\StockAdjustmentRepositoryInterface;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class CreateStockAdjustmentAction
{
    public function __construct(
        private readonly StockAdjustmentRepositoryInterface $repository
    ) {}

    public function execute(array $data, int $userId): StockAdjustment
    {
        return DB::transaction(function () use ($data, $userId) {
            // Trim notes
            if (isset($data['notes'])) {
                $data['notes'] = trim($data['notes']);
            }

            // Check reason-direction compatibility
            $reasonEnum = AdjustmentReason::tryFrom($data['reason_code'] ?? '');
            if ($reasonEnum && ! $reasonEnum->isCompatibleWith($data['direction'] ?? '')) {
                throw new DomainException("Alasan adjustment '{$reasonEnum->label()}' tidak kompatibel dengan arah {$data['direction']}.", 422);
            }

            // Check notes for OTHER
            if ($reasonEnum === AdjustmentReason::OTHER && (empty($data['notes']) || trim($data['notes']) === '')) {
                throw new DomainException('Catatan wajib diisi jika alasan penyesuaian adalah Lain-lain.', 422);
            }

            // Validate active location
            $location = Location::where('id', $data['location_id'])->where('is_active', true)->first();
            if (! $location) {
                throw new DomainException('Lokasi penyesuaian tidak ditemukan atau tidak aktif.', 422);
            }

            // Validate active products
            $items = $data['items'] ?? [];
            if (empty($items)) {
                throw new DomainException('Dokumen adjustment harus memiliki minimal 1 item.', 422);
            }

            $productIds = array_column($items, 'product_id');
            $activeProductsCount = Product::whereIn('id', $productIds)->where('is_active', true)->count();
            if ($activeProductsCount !== count(array_unique($productIds))) {
                throw new DomainException('Satu atau lebih produk tidak aktif.', 422);
            }

            $data['adjustment_number'] = $this->repository->generateAdjustmentNumber();
            $data['status'] = AdjustmentStatus::DRAFT;
            $data['created_by'] = $userId;

            return $this->repository->create($data);
        });
    }
}
