<?php

namespace App\Features\Inventory\Actions;

use App\Features\Inventory\Models\StockReceipt;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UpdateStockReceiptAction
{
    public function execute(StockReceipt $receipt, array $data): StockReceipt
    {
        if (empty($data['items'])) {
            throw new InvalidArgumentException('Receipt must have at least one item.');
        }

        return DB::transaction(function () use ($receipt, $data) {
            $lockedReceipt = StockReceipt::where('id', $receipt->id)->lockForUpdate()->first();

            if (! $lockedReceipt->isDraft()) {
                throw new DomainException('Only DRAFT receipts can be updated.', 409);
            }

            $lockedReceipt->update([
                'supplier_id' => $data['supplier_id'],
                'date' => $data['date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $lockedReceipt->items()->delete();

            $this->syncItems($lockedReceipt, $data['items']);

            return $lockedReceipt->load('items');
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
