<?php

namespace App\Features\Inventory\Actions;

use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Inventory\Repositories\Contracts\StockTransferRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CreateStockTransferAction
{
    public function __construct(
        private readonly StockTransferRepositoryInterface $transferRepository
    ) {}

    public function execute(array $data, ?int $userId = null): StockTransfer
    {
        return DB::transaction(function () use ($data, $userId) {
            $data['transfer_number'] = $this->transferRepository->generateTransferNumber();
            $data['status'] = TransferStatus::DRAFT;
            $data['created_by'] = $userId;

            return $this->transferRepository->create($data);
        });
    }
}
