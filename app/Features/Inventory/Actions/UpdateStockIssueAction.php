<?php

namespace App\Features\Inventory\Actions;

use App\Features\Inventory\Models\StockIssue;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UpdateStockIssueAction
{
    public function execute(StockIssue $issue, array $data): StockIssue
    {
        if (empty($data['items'])) {
            throw new InvalidArgumentException('Issue must have at least one item.');
        }

        return DB::transaction(function () use ($issue, $data) {
            $lockedIssue = StockIssue::where('id', $issue->id)->lockForUpdate()->first();

            if (! $lockedIssue->isDraft()) {
                throw new DomainException('Only DRAFT issues can be updated.', 409);
            }

            $lockedIssue->update([
                'purpose' => $data['purpose'],
                'date' => $data['date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $lockedIssue->items()->delete();

            $this->syncItems($lockedIssue, $data['items']);

            return $lockedIssue->load('items');
        });
    }

    private function syncItems($issue, array $items)
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

        $issue->items()->createMany($itemData);
    }
}
