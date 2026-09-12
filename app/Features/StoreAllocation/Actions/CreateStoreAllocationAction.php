<?php

namespace App\Features\StoreAllocation\Actions;

use App\Features\Auth\Models\User;
use App\Features\Inventory\DTOs\StockChangeDTO;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Services\StockMovementService;
use App\Features\Location\Enums\LocationType;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Store\Models\Store;
use App\Features\StoreAllocation\Models\StoreAllocation;
use App\Features\StoreAllocation\Repositories\Contracts\StoreAllocationRepositoryInterface;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class CreateStoreAllocationAction
{
    public function __construct(
        private readonly StoreAllocationRepositoryInterface $repository,
        private readonly StockMovementService $stockMovementService
    ) {}

    public function execute(array $data, ?int $userId = null): StoreAllocation
    {
        return DB::transaction(function () use ($data, $userId) {
            $store = Store::find($data['store_id']);
            if (! $store || ! $store->is_active) {
                throw new DomainException('Toko yang dipilih tidak aktif atau tidak ditemukan.', 422);
            }

            $location = Location::find($data['technician_location_id']);
            if (! $location || ! $location->is_active) {
                throw new DomainException('Lokasi teknisi tidak aktif atau tidak ditemukan.', 422);
            }

            if ($location->type !== LocationType::FIELD_PERSONNEL->value) {
                throw new DomainException('Lokasi stok teknisi harus bertipe FIELD_PERSONNEL.', 422);
            }

            $technician = User::find($data['technician_user_id']);
            if (! $technician) {
                throw new DomainException('Teknisi/lokasi stok tidak ditemukan.', 422);
            }

            if ((int) $location->user_id !== (int) $technician->id) {
                throw new DomainException('Lokasi stok harus terdaftar sebagai lokasi milik teknisi bersangkutan.', 422);
            }

            $allocationNumber = $this->repository->getNextAllocationNumber();

            $allocation = StoreAllocation::create([
                'allocation_number' => $allocationNumber,
                'technician_user_id' => $data['technician_user_id'],
                'technician_location_id' => $data['technician_location_id'],
                'store_id' => $data['store_id'],
                'allocated_at' => $data['allocated_at'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId ?? $data['technician_user_id'],
            ]);

            $movementDtos = [];

            foreach ($data['items'] as $item) {
                // Validate product to install
                $product = Product::find($item['product_id']);
                if (! $product || ! $product->is_active) {
                    throw new DomainException("Produk {$item['product_id']} tidak aktif atau tidak ditemukan.", 422);
                }

                $quantity = (string) $item['quantity'];

                $allocationItem = $allocation->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'serial_number' => $item['serial_number'] ?? null,
                    'pulled_product_id' => $item['pulled_product_id'] ?? null,
                    'pulled_quantity' => isset($item['pulled_quantity']) ? (string) $item['pulled_quantity'] : null,
                    'pulled_serial_number' => $item['pulled_serial_number'] ?? null,
                    'defective_reason' => $item['defective_reason'] ?? null,
                ]);

                // 1. Stock reduction: GOOD units installed at store
                $movementDtos[] = new StockChangeDTO(
                    productId: $item['product_id'],
                    locationId: $allocation->technician_location_id,
                    quantity: $quantity,
                    movementType: MovementType::STORE_ALLOCATION,
                    referenceType: StoreAllocation::class,
                    referenceId: $allocation->id,
                    referenceNumber: $allocation->allocation_number,
                    userId: $userId ?? $allocation->created_by,
                    occurredAt: $allocation->allocated_at->toDateTimeString(),
                    condition: 'GOOD'
                );

                // 2. Stock increase: DEFECTIVE units pulled from store into technician inventory
                if (! empty($item['pulled_product_id']) && ! empty($item['pulled_quantity']) && bccomp((string) $item['pulled_quantity'], '0', 4) > 0) {
                    $pulledProduct = Product::find($item['pulled_product_id']);
                    if (! $pulledProduct) {
                        throw new DomainException("Produk unit lama yang ditarik ({$item['pulled_product_id']}) tidak ditemukan.", 422);
                    }

                    $movementDtos[] = new StockChangeDTO(
                        productId: $item['pulled_product_id'],
                        locationId: $allocation->technician_location_id,
                        quantity: (string) $item['pulled_quantity'],
                        movementType: MovementType::REPLACEMENT_PULL,
                        referenceType: StoreAllocation::class,
                        referenceId: $allocation->id,
                        referenceNumber: $allocation->allocation_number,
                        userId: $userId ?? $allocation->created_by,
                        occurredAt: $allocation->allocated_at->toDateTimeString(),
                        condition: 'DEFECTIVE'
                    );
                }
            }

            // Record all inventory movements
            $this->stockMovementService->recordMultipleMovements($movementDtos);

            return $allocation->fresh([
                'technician',
                'location',
                'store',
                'creator',
                'items.product',
                'items.pulledProduct',
            ]);
        }, 5);
    }
}
