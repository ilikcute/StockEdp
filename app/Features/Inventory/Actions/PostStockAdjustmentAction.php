<?php

namespace App\Features\Inventory\Actions;

use App\Features\Auth\Models\User;
use App\Features\Inventory\DTOs\StockChangeDTO;
use App\Features\Inventory\Enums\AdjustmentReason;
use App\Features\Inventory\Enums\AdjustmentStatus;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Models\StockAdjustment;
use App\Features\Inventory\Services\StockMovementService;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class PostStockAdjustmentAction
{
    public function __construct(
        private readonly StockMovementService $stockMovementService
    ) {}

    public function execute(StockAdjustment $adjustment, int $userId): StockAdjustment
    {
        return DB::transaction(function () use ($adjustment, $userId) {
            // 1. Lock StockAdjustment header
            $lockedAdjustment = StockAdjustment::where('id', $adjustment->id)->lockForUpdate()->first();

            if (! $lockedAdjustment) {
                throw new DomainException('Dokumen adjustment tidak ditemukan.', 404);
            }

            // 2. Baca ulang status, pastikan DRAFT
            if ($lockedAdjustment->isPosted()) {
                throw new DomainException('Dokumen adjustment sudah diposting sebelumnya.', 409);
            }

            if (! $lockedAdjustment->isDraft()) {
                throw new DomainException('Hanya adjustment berstatus DRAFT yang dapat diposting.', 409);
            }

            // 3. Maker-Checker check: created_by wajib berbeda dengan posted_by
            if ($lockedAdjustment->created_by === $userId) {
                throw new DomainException('Pembuat adjustment tidak boleh mem-posting dokumen miliknya sendiri (maker-checker violation).', 403);
            }

            // 4. Validasi Location access
            $user = User::find($userId);
            if ($user && ! in_array($lockedAdjustment->location_id, $user->getAllowedLocationIds(), true)) {
                throw new DomainException('Pengguna tidak memiliki akses ke lokasi dokumen ini.', 403);
            }

            // 5. Load items & pastikan tidak kosong
            $lockedAdjustment->load('items');
            if ($lockedAdjustment->items->isEmpty()) {
                throw new DomainException('Dokumen adjustment tidak memiliki item.', 422);
            }

            // 6. Validasi Location aktif
            $location = Location::where('id', $lockedAdjustment->location_id)->where('is_active', true)->first();
            if (! $location) {
                throw new DomainException('Lokasi penyesuaian sudah tidak aktif.', 422);
            }

            // 7. Validasi Product aktif (batch)
            $productIds = $lockedAdjustment->items->pluck('product_id')->unique()->toArray();
            $activeProductsCount = Product::whereIn('id', $productIds)->where('is_active', true)->count();
            if ($activeProductsCount !== count($productIds)) {
                throw new DomainException('Satu atau lebih produk dalam dokumen ini sudah tidak aktif.', 422);
            }

            // 8. Validasi reason & direction compatibility
            $reasonEnum = $lockedAdjustment->reason_code;
            if ($reasonEnum && ! $reasonEnum->isCompatibleWith($lockedAdjustment->direction)) {
                throw new DomainException("Alasan adjustment '{$reasonEnum->label()}' tidak kompatibel dengan arah {$lockedAdjustment->direction}.", 422);
            }

            if ($reasonEnum === AdjustmentReason::OTHER && (empty($lockedAdjustment->notes) || trim($lockedAdjustment->notes) === '')) {
                throw new DomainException('Catatan wajib diisi jika alasan penyesuaian adalah Lain-lain.', 422);
            }

            // 9. Determine movement type
            $movementType = $lockedAdjustment->direction === 'INCREASE'
                ? MovementType::ADJUSTMENT_IN
                : MovementType::ADJUSTMENT_OUT;

            // Build StockChangeDTOs
            $dtos = [];
            foreach ($lockedAdjustment->items as $item) {
                $dtos[] = new StockChangeDTO(
                    productId: $item->product_id,
                    locationId: $lockedAdjustment->location_id,
                    quantity: (string) $item->quantity,
                    movementType: $movementType,
                    referenceType: StockAdjustment::class,
                    referenceId: $lockedAdjustment->id,
                    referenceNumber: $lockedAdjustment->adjustment_number,
                    userId: $userId,
                    occurredAt: $lockedAdjustment->adjustment_date->format('Y-m-d H:i:s')
                );
            }

            // 10-14. Record movements & update balances in consistent lock order
            $this->stockMovementService->recordMultipleMovements($dtos);

            // 15-16. Update header status to POSTED
            $lockedAdjustment->update([
                'status' => AdjustmentStatus::POSTED,
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            return $lockedAdjustment;
        });
    }
}
