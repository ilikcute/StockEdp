<?php

namespace App\Console\Commands;

use App\Features\Inventory\Actions\CancelStockAdjustmentAction;
use App\Features\Inventory\Actions\CancelStockOpnameAction;
use App\Features\Inventory\Actions\CancelStockTransferAction;
use App\Features\Inventory\Actions\CompleteStockOpnameAction;
use App\Features\Inventory\Actions\PostStockAdjustmentAction;
use App\Features\Inventory\Actions\PostStockIssueAction;
use App\Features\Inventory\Actions\PostStockOpnameAction;
use App\Features\Inventory\Actions\PostStockReceiptAction;
use App\Features\Inventory\Actions\ReceiveStockTransferAction;
use App\Features\Inventory\Actions\ReopenStockOpnameAction;
use App\Features\Inventory\Actions\SendStockTransferAction;
use App\Features\Inventory\Actions\StartStockOpnameAction;
use App\Features\Inventory\Models\StockAdjustment;
use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Models\StockOpname;
use App\Features\Inventory\Models\StockReceipt;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Inventory\Services\InventoryFreezeService;
use Illuminate\Console\Command;

class TestConcurrencyWorker extends Command
{
    protected $signature = 'test:concurrency-worker {--type=} {--id=} {--user=}';

    protected $description = 'Worker to test concurrent DB locking';

    public function handle(
        PostStockReceiptAction $postReceiptAction,
        PostStockIssueAction $postIssueAction,
        SendStockTransferAction $sendTransferAction,
        ReceiveStockTransferAction $receiveTransferAction,
        CancelStockTransferAction $cancelTransferAction
    ) {
        $type = $this->option('type');
        $id = $this->option('id');
        $userId = $this->option('user');

        try {
            if ($type === 'receipt') {
                $receipt = StockReceipt::findOrFail($id);
                $postReceiptAction->execute($receipt, $userId);
            } elseif ($type === 'issue') {
                $issue = StockIssue::findOrFail($id);
                $postIssueAction->execute($issue, $userId);
            } elseif ($type === 'transfer-send') {
                $transfer = StockTransfer::findOrFail($id);
                $sendTransferAction->execute($transfer, $userId);
            } elseif ($type === 'transfer-receive') {
                $transfer = StockTransfer::findOrFail($id);
                $receiveTransferAction->execute($transfer, $userId);
            } elseif ($type === 'transfer-cancel') {
                $transfer = StockTransfer::findOrFail($id);
                $cancelTransferAction->execute($transfer, $userId);
            } elseif ($type === 'adjustment-post') {
                $adjustment = StockAdjustment::findOrFail($id);
                app(PostStockAdjustmentAction::class)->execute($adjustment, $userId);
            } elseif ($type === 'adjustment-cancel') {
                $adjustment = StockAdjustment::findOrFail($id);
                app(CancelStockAdjustmentAction::class)->execute($adjustment, $userId);
            } elseif ($type === 'freeze') {
                app(InventoryFreezeService::class)->freezeLocation((int) $id, (int) $userId);
            } elseif ($type === 'unfreeze') {
                app(InventoryFreezeService::class)->unfreezeLocation((int) $id, (int) $userId);
            } elseif ($type === 'opname-start') {
                $opname = StockOpname::findOrFail($id);
                app(StartStockOpnameAction::class)->execute($opname, (int) $userId);
            } elseif ($type === 'opname-complete') {
                $opname = StockOpname::findOrFail($id);
                app(CompleteStockOpnameAction::class)->execute($opname, (int) $userId);
            } elseif ($type === 'opname-reopen') {
                $opname = StockOpname::findOrFail($id);
                app(ReopenStockOpnameAction::class)->execute($opname, 'Recount requested', (int) $userId);
            } elseif ($type === 'opname-post') {
                $opname = StockOpname::findOrFail($id);
                app(PostStockOpnameAction::class)->execute($opname, (int) $userId);
            } elseif ($type === 'opname-cancel') {
                $opname = StockOpname::findOrFail($id);
                app(CancelStockOpnameAction::class)->execute($opname, 'Canceled via test worker', (int) $userId);
            }

            $this->info(json_encode(['status' => 'success']));

            return 0;
        } catch (\Throwable $e) {
            $this->error(json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'class' => get_class($e),
                'code' => $e->getCode(),
            ]));

            return 1;
        }
    }
}
