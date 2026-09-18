<?php

namespace App\Features\Inventory\Actions;

use App\Features\Audit\Services\ActivityLogger;
use App\Features\Inventory\Enums\AdjustmentStatus;
use App\Features\Inventory\Enums\StockCondition;
use App\Features\Inventory\Models\StockAdjustment;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Inventory\Repositories\Contracts\InventoryBalanceRepositoryInterface;
use App\Features\MonthEnd\Services\PeriodLockService;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class DeleteStockAdjustmentAction
{
    public function __construct(
        private readonly InventoryBalanceRepositoryInterface $balanceRepository,
        private readonly PeriodLockService $periodLockService
    ) {}

    public function execute(StockAdjustment $adjustment, ?int $userId = null): void
    {
        DB::transaction(function () use ($adjustment, $userId) {
            /** @var StockAdjustment|null $lockedAdjustment */
            $lockedAdjustment = StockAdjustment::query()->where('id', $adjustment->id)->lockForUpdate()->first();
            if (! $lockedAdjustment) {
                return;
            }

            $lockedAdjustment->loadMissing('items.product', 'location');

            if ($lockedAdjustment->isPosted()) {
                $this->periodLockService->ensureDateIsOpen(
                    $lockedAdjustment->adjustment_date,
                    'menghapus Penyesuaian Stok (Stock Adjustment)'
                );

                $condition = StockCondition::GOOD;

                foreach ($lockedAdjustment->items as $item) {
                    $balance = $this->balanceRepository->lockBalanceForUpdate(
                        $item->product_id,
                        $lockedAdjustment->location_id,
                        $condition
                    );

                    if ($lockedAdjustment->direction === 'INCREASE') {
                        // Original added stock; rollback must deduct
                        if (bccomp($balance->quantity, (string) $item->quantity, 4) < 0) {
                            $productName = $item->product?->name ?? "SKU {$item->product_id}";
                            $locName = $lockedAdjustment->location?->name ?? "Lokasi {$lockedAdjustment->location_id}";
                            throw new DomainException(
                                "Saldo stok {$productName} di {$locName} saat ini ({$balance->quantity}) tidak mencukupi untuk dibatalkan (memerlukan {$item->quantity}).",
                                422
                            );
                        }

                        $balance->quantity = bcsub($balance->quantity, (string) $item->quantity, 4);
                    } else {
                        // Original deducted stock; rollback adds it back
                        $balance->quantity = bcadd($balance->quantity, (string) $item->quantity, 4);
                    }

                    $balance->save();
                }

                // Delete associated stock movements
                StockMovement::where('reference_type', StockAdjustment::class)
                    ->where('reference_id', $lockedAdjustment->id)
                    ->delete();
            }

            try {
                app(ActivityLogger::class)->record(
                    module: 'stock_adjustments',
                    action: 'delete',
                    description: "Menghapus dokumen penyesuaian stok {$lockedAdjustment->adjustment_number}",
                    subject: $lockedAdjustment,
                    properties: [
                        'adjustment_number' => $lockedAdjustment->adjustment_number,
                        'status' => $lockedAdjustment->status?->value ?? (string) $lockedAdjustment->status,
                        'location_id' => $lockedAdjustment->location_id,
                        'items_count' => $lockedAdjustment->items->count(),
                    ],
                    userId: $userId
                );
            } catch (\Throwable $e) {
                report($e);
            }

            $lockedAdjustment->items()->delete();
            $lockedAdjustment->delete();
        });
    }
}
