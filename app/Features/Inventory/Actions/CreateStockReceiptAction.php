<?php

namespace App\Features\Inventory\Actions;

use App\Features\Inventory\Enums\ReceiptStatus;
use App\Features\Inventory\Models\StockReceipt;
use App\Features\Inventory\Repositories\Contracts\StockReceiptRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateStockReceiptAction
{
    public function __construct(
        private readonly StockReceiptRepositoryInterface $repository
    ) {}

    public function execute(array $data, int $userId): StockReceipt
    {
        if (empty($data['items'])) {
            throw new InvalidArgumentException('Receipt must have at least one item.');
        }

        return DB::transaction(function () use ($data, $userId) {
            $receiptData = [
                'receipt_number' => $this->repository->generateReceiptNumber(),
                'supplier_id' => $data['supplier_id'],
                'date' => $data['date'],
                'notes' => $data['notes'] ?? null,
                'status' => ReceiptStatus::DRAFT,
                'created_by' => $userId,
            ];

            $receipt = $this->repository->create($receiptData);

            $this->syncItems($receipt, $data['items']);

            return $receipt;
        });
    }

    private function syncItems($receipt, array $items)
    {
        $itemData = [];
        $combinations = [];

        foreach ($items as $item) {
            if ($item['quantity'] <= 0) {
                throw new InvalidArgumentException('Quantity must be greater than zero.');
            }

            $key = $item['product_id'].'-'.$item['location_id'];
            if (isset($combinations[$key])) {
                throw new InvalidArgumentException('Duplicate product and location combination in items.');
            }
            $combinations[$key] = true;

            $itemData[] = [
                'product_id' => $item['product_id'],
                'location_id' => $item['location_id'],
                'quantity' => $item['quantity'],
            ];
        }

        $receipt->items()->createMany($itemData);
    }
}
