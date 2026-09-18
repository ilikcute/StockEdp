<?php

namespace App\Features\Inventory\Actions;

use App\Features\Audit\Services\ActivityLogger;
use App\Features\Inventory\Enums\StockCondition;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Inventory\Models\StockReceipt;
use App\Features\Inventory\Repositories\Contracts\InventoryBalanceRepositoryInterface;
use App\Features\MonthEnd\Services\PeriodLockService;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class DeleteStockReceiptAction
{
    public function __construct(
        private readonly InventoryBalanceRepositoryInterface $balanceRepository,
        private readonly PeriodLockService $periodLockService
    ) {}

    public function execute(StockReceipt $receipt, ?int $userId = null): void
    {
        DB::transaction(function () use ($receipt, $userId) {
            /** @var StockReceipt|null $lockedReceipt */
            $lockedReceipt = StockReceipt::query()->where('id', $receipt->id)->lockForUpdate()->first();
            if (! $lockedReceipt) {
                return;
            }

            $lockedReceipt->loadMissing('items.product', 'items.location');

            if ($lockedReceipt->isPosted()) {
                $this->periodLockService->ensureDateIsOpen(
                    $lockedReceipt->date,
                    'menghapus Penerimaan Barang (Stock Receipt)'
                );

                // Rollback inventory balances
                foreach ($lockedReceipt->items as $item) {
                    $balance = $this->balanceRepository->lockBalanceForUpdate(
                        $item->product_id,
                        $item->location_id,
                        StockCondition::GOOD
                    );

                    if (bccomp($balance->quantity, (string) $item->quantity, 4) < 0) {
                        $productName = $item->product?->name ?? "SKU {$item->product_id}";
                        $locName = $item->location?->name ?? "Lokasi {$item->location_id}";
                        throw new DomainException(
                            "Saldo stok {$productName} di {$locName} saat ini ({$balance->quantity}) tidak mencukupi untuk ditarik kembali ({$item->quantity}) karena sebagian stok sudah terpakai.",
                            422
                        );
                    }

                    $balance->quantity = bcsub($balance->quantity, (string) $item->quantity, 4);
                    $balance->save();
                }

                // Delete associated stock movements
                StockMovement::where('reference_type', StockReceipt::class)
                    ->where('reference_id', $lockedReceipt->id)
                    ->delete();
            }

            try {
                app(ActivityLogger::class)->record(
                    module: 'stock_receipts',
                    action: 'delete',
                    description: "Menghapus dokumen penerimaan barang {$lockedReceipt->receipt_number}",
                    subject: $lockedReceipt,
                    properties: [
                        'receipt_number' => $lockedReceipt->receipt_number,
                        'status' => $lockedReceipt->status?->value ?? (string) $lockedReceipt->status,
                        'items_count' => $lockedReceipt->items->count(),
                    ],
                    userId: $userId
                );
            } catch (\Throwable $e) {
                report($e);
            }

            $lockedReceipt->items()->delete();
            $lockedReceipt->delete();
        });
    }
}
