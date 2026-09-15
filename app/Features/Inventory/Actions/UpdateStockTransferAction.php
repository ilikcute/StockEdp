<?php

namespace App\Features\Inventory\Actions;

use App\Features\Inventory\Models\StockTransfer;
use App\Features\Inventory\Repositories\Contracts\StockTransferRepositoryInterface;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class UpdateStockTransferAction
{
    public function __construct(
        private readonly StockTransferRepositoryInterface $transferRepository
    ) {}

    public function execute(StockTransfer $transfer, array $data, ?int $userId = null): StockTransfer
    {
        $periodLock = app(\App\Features\MonthEnd\Services\PeriodLockService::class);
        $periodLock->ensureDateIsOpen($transfer->transfer_date, 'mengubah Transfer Antar Gudang');
        if (isset($data['transfer_date']) && $data['transfer_date'] !== $transfer->transfer_date) {
            $periodLock->ensureDateIsOpen($data['transfer_date'], 'mengubah tanggal Transfer Antar Gudang');
        }

        return DB::transaction(function () use ($transfer, $data, $userId) {
            $lockedTransfer = StockTransfer::where('id', $transfer->id)->lockForUpdate()->first();

            if (! $lockedTransfer->isDraft()) {
                throw new DomainException('Only DRAFT transfers can be updated.', 409);
            }

            $data['updated_by'] = $userId;

            return $this->transferRepository->update($lockedTransfer, $data);
        });
    }
}
