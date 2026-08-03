<?php

namespace App\Features\Inventory\Services;

use App\Features\Inventory\DTOs\StockChangeDTO;
use App\Features\Inventory\Exceptions\InsufficientStockException;
use App\Features\Inventory\Repositories\Contracts\InventoryBalanceRepositoryInterface;
use App\Features\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockMovementService
{
    private readonly InventoryFreezeService $freezeService;

    public function __construct(
        private readonly InventoryBalanceRepositoryInterface $balanceRepository,
        private readonly StockMovementRepositoryInterface $movementRepository,
        ?InventoryFreezeService $freezeService = null
    ) {
        $this->freezeService = $freezeService ?? app(InventoryFreezeService::class);
    }

    /**
     * Record a single stock movement and update balance with freeze check.
     */
    public function recordMovement(StockChangeDTO $dto, ?int $allowedOpnameId = null): void
    {
        DB::transaction(function () use ($dto, $allowedOpnameId) {
            if (bccomp($dto->quantity, '0.0000', 4) <= 0) {
                throw new \InvalidArgumentException('Quantity must be greater than zero.');
            }

            // Step 2 & 3 Global Lock Order: Lock & Validate Location operation row
            $this->freezeService->lockAndValidateLocations([$dto->locationId], $allowedOpnameId);

            // Step 4 Global Lock Order: Lock balance for update
            $balance = $this->balanceRepository->lockBalanceForUpdate($dto->productId, $dto->locationId);

            $quantityBefore = $balance->quantity;
            $changeAmount = $dto->quantity;

            if ($dto->movementType->isAddition()) {
                $quantityAfter = bcadd($quantityBefore, $changeAmount, 4);
            } else {
                $quantityAfter = bcsub($quantityBefore, $changeAmount, 4);

                // Reject negative balance
                if (bccomp($quantityAfter, '0.0000', 4) < 0) {
                    throw new InsufficientStockException;
                }
            }

            // Update balance
            $balance->quantity = $quantityAfter;
            $balance->save();

            // Create movement
            $this->movementRepository->create([
                'movement_id' => Str::uuid()->toString(),
                'product_id' => $dto->productId,
                'location_id' => $dto->locationId,
                'movement_type' => $dto->movementType->value,
                'quantity' => $changeAmount,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reference_type' => $dto->referenceType,
                'reference_id' => $dto->referenceId,
                'reference_number' => $dto->referenceNumber,
                'occurred_at' => $dto->occurredAt ?? now(),
                'created_by' => $dto->userId,
            ]);
        });
    }

    /**
     * Process multiple movements ensuring deadlock prevention by sorting Location locks and Product locks.
     *
     * @param  StockChangeDTO[]  $dtos
     */
    public function recordMultipleMovements(array $dtos, ?int $allowedOpnameId = null): void
    {
        if (empty($dtos)) {
            return;
        }

        // Extract all location IDs
        $locationIds = array_column($dtos, 'locationId');

        // Sort DTOs by product_id and location_id ascending before balance locking
        usort($dtos, function (StockChangeDTO $a, StockChangeDTO $b) {
            if ($a->productId === $b->productId) {
                return $a->locationId <=> $b->locationId;
            }

            return $a->productId <=> $b->productId;
        });

        DB::transaction(function () use ($dtos, $locationIds, $allowedOpnameId) {
            // Lock and validate locations within the active transaction
            $this->freezeService->lockAndValidateLocations($locationIds, $allowedOpnameId);

            foreach ($dtos as $dto) {
                // Record individual movement
                $this->recordSingleMovementInternal($dto);
            }
        });
    }

    /**
     * Internal movement recorder assuming location lock and freeze validation has already occurred.
     */
    private function recordSingleMovementInternal(StockChangeDTO $dto): void
    {
        if (bccomp($dto->quantity, '0.0000', 4) <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        $balance = $this->balanceRepository->lockBalanceForUpdate($dto->productId, $dto->locationId);

        $quantityBefore = $balance->quantity;
        $changeAmount = $dto->quantity;

        if ($dto->movementType->isAddition()) {
            $quantityAfter = bcadd($quantityBefore, $changeAmount, 4);
        } else {
            $quantityAfter = bcsub($quantityBefore, $changeAmount, 4);

            if (bccomp($quantityAfter, '0.0000', 4) < 0) {
                throw new InsufficientStockException;
            }
        }

        $balance->quantity = $quantityAfter;
        $balance->save();

        $this->movementRepository->create([
            'movement_id' => Str::uuid()->toString(),
            'product_id' => $dto->productId,
            'location_id' => $dto->locationId,
            'movement_type' => $dto->movementType->value,
            'quantity' => $changeAmount,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'reference_type' => $dto->referenceType,
            'reference_id' => $dto->referenceId,
            'reference_number' => $dto->referenceNumber,
            'occurred_at' => $dto->occurredAt ?? now(),
            'created_by' => $dto->userId,
        ]);
    }
}
