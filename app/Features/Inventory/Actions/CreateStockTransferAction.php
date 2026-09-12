<?php

namespace App\Features\Inventory\Actions;

use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Inventory\Enums\TransferType;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Inventory\Repositories\Contracts\StockTransferRepositoryInterface;
use App\Features\Location\Enums\LocationType;
use App\Features\Location\Models\Location;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class CreateStockTransferAction
{
    public function __construct(
        private readonly StockTransferRepositoryInterface $transferRepository
    ) {}

    public function execute(array $data, ?int $userId = null): StockTransfer
    {
        return DB::transaction(function () use ($data, $userId) {
            $data['transfer_type'] = $data['transfer_type'] ?? TransferType::TRANSFER->value;

            if ($data['transfer_type'] === TransferType::RETURN->value) {
                $this->validateReturnDestinations($data['origin_location_id'], $data['destination_location_id']);
            }

            $data['transfer_number'] = $this->transferRepository->generateTransferNumber();
            $data['status'] = TransferStatus::DRAFT;
            $data['created_by'] = $userId;

            return $this->transferRepository->create($data);
        });
    }

    private function validateReturnDestinations(int $originLocationId, int $destinationLocationId): void
    {
        $origin = Location::find($originLocationId);
        $destination = Location::find($destinationLocationId);

        if (! $origin || $origin->type !== LocationType::FIELD_PERSONNEL->value) {
            throw new DomainException('Retur hanya dapat dikirim dari lokasi personel lapangan (teknisi).', 422);
        }

        if (! $destination || ! in_array($destination->type, [
            LocationType::MAIN_WAREHOUSE->value,
            LocationType::DAMAGED_STORAGE->value,
        ], true)) {
            throw new DomainException('Tujuan retur harus Gudang Induk (MAIN_WAREHOUSE) atau Gudang Afkir (DAMAGED_STORAGE).', 422);
        }
    }
}
