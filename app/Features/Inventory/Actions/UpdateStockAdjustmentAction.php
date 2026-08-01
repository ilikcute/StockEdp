<?php

namespace App\Features\Inventory\Actions;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\AdjustmentReason;
use App\Features\Inventory\Models\StockAdjustment;
use App\Features\Inventory\Repositories\Contracts\StockAdjustmentRepositoryInterface;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class UpdateStockAdjustmentAction
{
    public function __construct(
        private readonly StockAdjustmentRepositoryInterface $repository
    ) {}

    public function execute(StockAdjustment $adjustment, array $data, int $userId): StockAdjustment
    {
        return DB::transaction(function () use ($adjustment, $data, $userId) {
            $lockedAdjustment = StockAdjustment::where('id', $adjustment->id)->lockForUpdate()->first();

            if (! $lockedAdjustment || ! $lockedAdjustment->isDraft()) {
                throw new DomainException('Hanya adjustment berstatus DRAFT yang dapat diperbarui.', 409);
            }

            $user = User::find($userId);
            // Ownership rule check for WAREHOUSE_OFFICER
            if ($user && $user->hasRole(RoleCode::WAREHOUSE_OFFICER->value) && ! $user->hasRole(RoleCode::ADMIN->value) && ! $user->hasRole(RoleCode::INVENTORY_SUPERVISOR->value)) {
                if ($lockedAdjustment->created_by !== $userId) {
                    throw new DomainException('Petugas gudang hanya dapat mengedit draft adjustment miliknya sendiri.', 403);
                }
            }

            // Trim notes
            if (isset($data['notes'])) {
                $data['notes'] = trim($data['notes']);
            }

            // Check reason-direction compatibility
            $direction = $data['direction'] ?? $lockedAdjustment->direction;
            $reasonCode = $data['reason_code'] ?? $lockedAdjustment->reason_code->value;
            $reasonEnum = AdjustmentReason::tryFrom($reasonCode);
            if ($reasonEnum && ! $reasonEnum->isCompatibleWith($direction)) {
                throw new DomainException("Alasan adjustment '{$reasonEnum->label()}' tidak kompatibel dengan arah {$direction}.", 422);
            }

            // Check notes for OTHER
            $notes = array_key_exists('notes', $data) ? $data['notes'] : $lockedAdjustment->notes;
            if ($reasonEnum === AdjustmentReason::OTHER && (empty($notes) || trim($notes) === '')) {
                throw new DomainException('Catatan wajib diisi jika alasan penyesuaian adalah Lain-lain.', 422);
            }

            // Validate active location
            $locationId = $data['location_id'] ?? $lockedAdjustment->location_id;
            $location = Location::where('id', $locationId)->where('is_active', true)->first();
            if (! $location) {
                throw new DomainException('Lokasi penyesuaian tidak ditemukan atau tidak aktif.', 422);
            }

            // Validate active products
            $items = $data['items'] ?? [];
            if (! empty($items)) {
                $productIds = array_column($items, 'product_id');
                $activeProductsCount = Product::whereIn('id', $productIds)->where('is_active', true)->count();
                if ($activeProductsCount !== count(array_unique($productIds))) {
                    throw new DomainException('Satu atau lebih produk tidak aktif.', 422);
                }
            }

            $data['updated_by'] = $userId;

            return $this->repository->update($lockedAdjustment, $data);
        });
    }
}
