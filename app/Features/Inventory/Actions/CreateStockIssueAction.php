<?php

namespace App\Features\Inventory\Actions;

use App\Features\Inventory\Enums\IssueStatus;
use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Repositories\Contracts\StockIssueRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateStockIssueAction
{
    public function __construct(
        private readonly StockIssueRepositoryInterface $repository
    ) {}

    public function execute(array $data, int $userId): StockIssue
    {
        if (empty($data['items'])) {
            throw new InvalidArgumentException('Issue must have at least one item.');
        }

        return DB::transaction(function () use ($data, $userId) {
            $issueData = [
                'issue_number' => $this->repository->generateIssueNumber(),
                'purpose' => $data['purpose'],
                'date' => $data['date'],
                'notes' => $data['notes'] ?? null,
                'status' => IssueStatus::DRAFT,
                'created_by' => $userId,
            ];

            $issue = $this->repository->create($issueData);

            $this->syncItems($issue, $data['items']);

            return $issue;
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
