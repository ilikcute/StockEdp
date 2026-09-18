<?php

namespace App\Features\StoreAllocation\Actions;

use App\Features\Audit\Services\ActivityLogger;
use App\Features\Inventory\Enums\StockCondition;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Inventory\Repositories\Contracts\InventoryBalanceRepositoryInterface;
use App\Features\MonthEnd\Services\PeriodLockService;
use App\Features\ProductSerial\Enums\SerialStatus;
use App\Features\ProductSerial\Models\ProductSerial;
use App\Features\ProductSerial\Models\ProductSerialMovement;
use App\Features\StoreAllocation\Models\StoreAllocation;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class DeleteStoreAllocationAction
{
    public function __construct(
        private readonly InventoryBalanceRepositoryInterface $balanceRepository,
        private readonly PeriodLockService $periodLockService
    ) {}

    public function execute(StoreAllocation $allocation, ?int $userId = null): void
    {
        $this->periodLockService->ensureDateIsOpen(
            $allocation->allocated_at,
            'menghapus Alokasi Toko (Store Allocation)'
        );

        DB::transaction(function () use ($allocation, $userId) {
            $allocation->loadMissing(['items', 'store', 'location', 'technician']);

            // 1. Rollback Stock Balances & Product Serials for each item
            foreach ($allocation->items as $item) {
                // A. Revert installed GOOD units: add quantity back to technician location
                if (bccomp((string) $item->quantity, '0', 4) > 0) {
                    $goodBalance = $this->balanceRepository->lockBalanceForUpdate(
                        $item->product_id,
                        $allocation->technician_location_id,
                        StockCondition::GOOD
                    );
                    $goodBalance->quantity = bcadd($goodBalance->quantity, (string) $item->quantity, 4);
                    $goodBalance->save();
                }

                // B. Revert pulled DEFECTIVE units: deduct pulled_quantity from technician location
                if (! empty($item->pulled_product_id) && ! empty($item->pulled_quantity) && bccomp((string) $item->pulled_quantity, '0', 4) > 0) {
                    $defBalance = $this->balanceRepository->lockBalanceForUpdate(
                        $item->pulled_product_id,
                        $allocation->technician_location_id,
                        StockCondition::DEFECTIVE
                    );

                    if (bccomp($defBalance->quantity, (string) $item->pulled_quantity, 4) < 0) {
                        throw new DomainException(
                            "Saldo unit rusak hasil tarikan (SKU {$item->pulled_product_id}) di lokasi saat ini ({$defBalance->quantity}) tidak mencukupi untuk dibatalkan (memerlukan {$item->pulled_quantity}).",
                            422
                        );
                    }

                    $defBalance->quantity = bcsub($defBalance->quantity, (string) $item->pulled_quantity, 4);
                    $defBalance->save();
                }

                // C. Revert Serial Numbers
                if (! empty($item->serial_number)) {
                    /** @var ProductSerial|null $serial */
                    $serial = ProductSerial::where('serial_number', $item->serial_number)
                        ->where('product_id', $item->product_id)
                        ->first();

                    if ($serial) {
                        // Revert back to IN_STOCK at technician location
                        $serial->update([
                            'current_location_id' => $allocation->technician_location_id,
                            'current_store_id' => null,
                            'status' => SerialStatus::IN_STOCK,
                            'current_condition' => StockCondition::GOOD,
                        ]);

                        // Delete serial movements referencing this allocation
                        ProductSerialMovement::where('product_serial_id', $serial->id)
                            ->where('reference_type', StoreAllocation::class)
                            ->where('reference_id', $allocation->id)
                            ->delete();
                    }
                }

                if (! empty($item->pulled_serial_number) && ! empty($item->pulled_product_id)) {
                    /** @var ProductSerial|null $pulledSerial */
                    $pulledSerial = ProductSerial::where('serial_number', $item->pulled_serial_number)
                        ->where('product_id', $item->pulled_product_id)
                        ->first();

                    if ($pulledSerial) {
                        // Delete serial movements referencing this allocation
                        ProductSerialMovement::where('product_serial_id', $pulledSerial->id)
                            ->where('reference_type', StoreAllocation::class)
                            ->where('reference_id', $allocation->id)
                            ->delete();

                        // Check if there are other movements; if none, delete the serial if it was registered during pull
                        $remainingMovements = ProductSerialMovement::where('product_serial_id', $pulledSerial->id)->count();
                        if ($remainingMovements === 0) {
                            $pulledSerial->delete();
                        } else {
                            $pulledSerial->update([
                                'current_location_id' => null,
                                'current_store_id' => $allocation->store_id,
                                'status' => SerialStatus::INSTALLED,
                            ]);
                        }
                    }
                }
            }

            // 2. Delete Stock Movements
            StockMovement::where('reference_type', StoreAllocation::class)
                ->where('reference_id', $allocation->id)
                ->delete();

            // 3. Log Audit
            try {
                app(ActivityLogger::class)->record(
                    module: 'store_allocations',
                    action: 'delete',
                    description: "Menghapus dokumen alokasi toko {$allocation->allocation_number} dan mengembalikan saldo fisik",
                    subject: $allocation,
                    properties: [
                        'allocation_number' => $allocation->allocation_number,
                        'store_id' => $allocation->store_id,
                        'technician_location_id' => $allocation->technician_location_id,
                        'items_count' => $allocation->items->count(),
                    ],
                    userId: $userId
                );
            } catch (\Throwable $e) {
                report($e);
            }

            // 4. Delete items and document
            $allocation->items()->delete();
            $allocation->delete();
        });
    }
}
