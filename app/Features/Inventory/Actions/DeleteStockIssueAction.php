<?php

namespace App\Features\Inventory\Actions;

use App\Features\Audit\Services\ActivityLogger;
use App\Features\Inventory\Enums\StockCondition;
use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Inventory\Repositories\Contracts\InventoryBalanceRepositoryInterface;
use App\Features\MonthEnd\Services\PeriodLockService;
use Illuminate\Support\Facades\DB;

class DeleteStockIssueAction
{
    public function __construct(
        private readonly InventoryBalanceRepositoryInterface $balanceRepository,
        private readonly PeriodLockService $periodLockService
    ) {}

    public function execute(StockIssue $issue, ?int $userId = null): void
    {
        DB::transaction(function () use ($issue, $userId) {
            /** @var StockIssue|null $lockedIssue */
            $lockedIssue = StockIssue::query()->where('id', $issue->id)->lockForUpdate()->first();
            if (! $lockedIssue) {
                return;
            }

            $lockedIssue->loadMissing('items.product', 'items.location');

            if ($lockedIssue->isPosted()) {
                $this->periodLockService->ensureDateIsOpen(
                    $lockedIssue->date,
                    'menghapus Pengeluaran Barang (Stock Issue)'
                );

                // Revert inventory balances: add deducted quantity back
                foreach ($lockedIssue->items as $item) {
                    $balance = $this->balanceRepository->lockBalanceForUpdate(
                        $item->product_id,
                        $item->location_id,
                        StockCondition::GOOD
                    );

                    $balance->quantity = bcadd($balance->quantity, (string) $item->quantity, 4);
                    $balance->save();
                }

                // Delete associated stock movements
                StockMovement::where('reference_type', StockIssue::class)
                    ->where('reference_id', $lockedIssue->id)
                    ->delete();
            }

            try {
                app(ActivityLogger::class)->record(
                    module: 'stock_issues',
                    action: 'delete',
                    description: "Menghapus dokumen pengeluaran barang {$lockedIssue->issue_number}",
                    subject: $lockedIssue,
                    properties: [
                        'issue_number' => $lockedIssue->issue_number,
                        'status' => $lockedIssue->status?->value ?? (string) $lockedIssue->status,
                        'items_count' => $lockedIssue->items->count(),
                    ],
                    userId: $userId
                );
            } catch (\Throwable $e) {
                report($e);
            }

            $lockedIssue->items()->delete();
            $lockedIssue->delete();
        });
    }
}
