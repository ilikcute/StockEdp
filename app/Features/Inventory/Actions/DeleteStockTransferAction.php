<?php

namespace App\Features\Inventory\Actions;

use App\Features\Audit\Services\ActivityLogger;
use App\Features\Inventory\Enums\StockCondition;
use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Inventory\Repositories\Contracts\InventoryBalanceRepositoryInterface;
use App\Features\MonthEnd\Services\PeriodLockService;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class DeleteStockTransferAction
{
    public function __construct(
        private readonly InventoryBalanceRepositoryInterface $balanceRepository,
        private readonly PeriodLockService $periodLockService
    ) {}

    public function execute(StockTransfer $transfer, ?int $userId = null): void
    {
        DB::transaction(function () use ($transfer, $userId) {
            /** @var StockTransfer|null $lockedTransfer */
            $lockedTransfer = StockTransfer::query()->where('id', $transfer->id)->lockForUpdate()->first();
            if (! $lockedTransfer) {
                return;
            }

            $lockedTransfer->loadMissing('items.product');

            $status = $lockedTransfer->status instanceof TransferStatus 
                ? $lockedTransfer->status 
                : TransferStatus::tryFrom($lockedTransfer->status);

            // If IN_TRANSIT or RECEIVED / DISCREPANCY, check period lock and rollback
            if ($status === TransferStatus::IN_TRANSIT || $status === TransferStatus::RECEIVED || $status === TransferStatus::DISCREPANCY) {
                $this->periodLockService->ensureDateIsOpen(
                    $lockedTransfer->transfer_date,
                    'menghapus Transfer Antar Gudang (Stock Transfer)'
                );

                foreach ($lockedTransfer->items as $item) {
                    $condition = StockCondition::GOOD; // Transfers move GOOD condition items

                    // 1. If received at destination, deduct from destination
                    if ($status === TransferStatus::RECEIVED || $status === TransferStatus::DISCREPANCY) {
                        $receivedQty = (string) ($item->received_quantity ?? $item->quantity);
                        if (bccomp($receivedQty, '0', 4) > 0) {
                            $destBalance = $this->balanceRepository->lockBalanceForUpdate(
                                $item->product_id,
                                $lockedTransfer->destination_location_id,
                                $condition
                            );

                            if (bccomp($destBalance->quantity, $receivedQty, 4) < 0) {
                                $productName = $item->product?->name ?? "SKU {$item->product_id}";
                                throw new DomainException(
                                    "Saldo stok {$productName} di lokasi tujuan ({$destBalance->quantity}) tidak mencukupi untuk dibatalkan (memerlukan {$receivedQty}).",
                                    422
                                );
                            }

                            $destBalance->quantity = bcsub($destBalance->quantity, $receivedQty, 4);
                            $destBalance->save();
                        }
                    }

                    // 2. Return sent quantity back to origin location
                    $sentQty = (string) $item->quantity;
                    if (bccomp($sentQty, '0', 4) > 0) {
                        $origBalance = $this->balanceRepository->lockBalanceForUpdate(
                            $item->product_id,
                            $lockedTransfer->origin_location_id,
                            $condition
                        );

                        $origBalance->quantity = bcadd($origBalance->quantity, $sentQty, 4);
                        $origBalance->save();
                    }
                }

                // Delete associated stock movements
                StockMovement::where('reference_type', StockTransfer::class)
                    ->where('reference_id', $lockedTransfer->id)
                    ->delete();
            }

            try {
                app(ActivityLogger::class)->record(
                    module: 'stock_transfers',
                    action: 'delete',
                    description: "Menghapus dokumen transfer barang {$lockedTransfer->transfer_number}",
                    subject: $lockedTransfer,
                    properties: [
                        'transfer_number' => $lockedTransfer->transfer_number,
                        'status' => $status?->value ?? (string) $lockedTransfer->status,
                        'origin_location_id' => $lockedTransfer->origin_location_id,
                        'destination_location_id' => $lockedTransfer->destination_location_id,
                        'items_count' => $lockedTransfer->items->count(),
                    ],
                    userId: $userId
                );
            } catch (\Throwable $e) {
                report($e);
            }

            $lockedTransfer->items()->delete();
            $lockedTransfer->delete();
        });
    }
}
