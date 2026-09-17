<?php

namespace App\Features\StoreAllocation\Actions;

use App\Features\Audit\Services\ActivityLogger;
use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\User;
use App\Features\Inventory\DTOs\StockChangeDTO;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Enums\StockCondition;
use App\Features\Inventory\Services\StockMovementService;
use App\Features\Location\Enums\LocationType;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Store\Models\Store;
use App\Features\StoreAllocation\Models\StoreAllocation;
use App\Features\StoreAllocation\Repositories\Contracts\StoreAllocationRepositoryInterface;
use App\Shared\Exceptions\DomainException;
use App\Features\ProductSerial\Services\ProductSerialService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CreateStoreAllocationAction
{
    public function __construct(
        private readonly StoreAllocationRepositoryInterface $repository,
        private readonly StockMovementService $stockMovementService,
        private readonly ProductSerialService $productSerialService
    ) {}

    public function execute(array $data, ?int $userId = null): StoreAllocation
    {
        // Tanggal pemasangan SELALU mengikuti tanggal sistem server (hari ini).
        // Nilai dari klien sengaja diabaikan agar alokasi tidak bisa di-backdate
        // ke periode yang sudah ditutup buku (menghindari kesalahan data stok).
        $data['allocated_at'] = now()->toDateString();

        app(\App\Features\MonthEnd\Services\PeriodLockService::class)->ensureDateIsOpen(
            $data['allocated_at'],
            'membuat Alokasi Toko (Store Allocation)'
        );

        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                return DB::transaction(function () use ($data, $userId) {
                    $store = Store::find($data['store_id']);
                    if (! $store || ! $store->is_active) {
                        throw new DomainException('Toko yang dipilih tidak aktif atau tidak ditemukan.', 422);
                    }

                    $location = Location::find($data['technician_location_id']);
                    if (! $location || ! $location->is_active) {
                        throw new DomainException('Lokasi asal alokasi tidak aktif atau tidak ditemukan.', 422);
                    }

                    if (! in_array($location->type, [LocationType::FIELD_PERSONNEL->value, LocationType::MAIN_WAREHOUSE->value], true)) {
                        throw new DomainException('Lokasi asal alokasi harus bertipe FIELD_PERSONNEL atau MAIN_WAREHOUSE.', 422);
                    }

                    $technician = User::find($data['technician_user_id']);
                    if (! $technician) {
                        throw new DomainException('Teknisi/petugas pelaksana alokasi tidak ditemukan.', 422);
                    }

                    if ($location->type === LocationType::FIELD_PERSONNEL->value && (int) $location->user_id !== (int) $technician->id) {
                        throw new DomainException('Lokasi stok teknisi harus terdaftar sebagai lokasi milik teknisi bersangkutan.', 422);
                    }

                    // Verifikasi Otorisasi Level-Objek (Pertahanan Berlapis / Defense-in-Depth)
                    if ($userId) {
                        /** @var User|null $actingUser */
                        $actingUser = User::query()->find($userId);
                        if ($actingUser instanceof User) {
                            $isOwn = (int) $data['technician_user_id'] === (int) $actingUser->id;

                            if (! $isOwn) {
                                $canCreateForOthers = $actingUser->hasRole(RoleCode::ADMIN)
                                    || $actingUser->hasRole(RoleCode::INVENTORY_SUPERVISOR)
                                    || $actingUser->hasPermissionTo(PermissionCode::STORE_ALLOCATIONS_CREATE_FOR_OTHERS);

                                if (! $canCreateForOthers) {
                                    throw new DomainException('Anda tidak memiliki hak akses untuk membuat alokasi toko atas nama teknisi lain.', 403);
                                }
                            }

                            // Jika lokasi bertipe FIELD_PERSONNEL, pastikan lokasi tersebut adalah lokasi milik pengguna sendiri (kecuali berwenang create_for_others)
                            if ($location->type === LocationType::FIELD_PERSONNEL->value && (int) $location->user_id !== (int) $actingUser->id) {
                                $canCreateForOthers = $actingUser->hasRole(RoleCode::ADMIN)
                                    || $actingUser->hasRole(RoleCode::INVENTORY_SUPERVISOR)
                                    || $actingUser->hasPermissionTo(PermissionCode::STORE_ALLOCATIONS_CREATE_FOR_OTHERS);

                                if (! $canCreateForOthers) {
                                    throw new DomainException('Lokasi stok teknisi bukan merupakan lokasi milik akun Anda.', 403);
                                }
                            }
                        }
                    }

                    $allocationNumber = $this->repository->getNextAllocationNumber();

                    $allocation = StoreAllocation::create([
                        'allocation_number' => $allocationNumber,
                        'technician_user_id' => $data['technician_user_id'],
                        'technician_location_id' => $data['technician_location_id'],
                        'store_id' => $data['store_id'],
                        'allocated_at' => $data['allocated_at'],
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
                            condition: StockCondition::GOOD
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
                                condition: StockCondition::DEFECTIVE
                            );
                        }

                        // 3. Pelacakan Siklus Hidup Serial Number
                        if (! empty($item['serial_number'])) {
                            $this->productSerialService->recordInstallation(
                                serialNumber: $item['serial_number'],
                                productId: (int) $item['product_id'],
                                storeId: (int) $allocation->store_id,
                                technicianLocationId: (int) $allocation->technician_location_id,
                                referenceType: StoreAllocation::class,
                                referenceId: $allocation->id,
                                referenceNumber: $allocation->allocation_number,
                                userId: $userId ?? $allocation->created_by,
                                occurredAt: $allocation->allocated_at,
                                notes: "Pemasangan unit di toko {$store->code} - {$store->name}"
                            );
                        }

                        if (! empty($item['pulled_serial_number']) && ! empty($item['pulled_product_id'])) {
                            $this->productSerialService->recordPull(
                                pulledSerialNumber: $item['pulled_serial_number'],
                                pulledProductId: (int) $item['pulled_product_id'],
                                storeId: (int) $allocation->store_id,
                                technicianLocationId: (int) $allocation->technician_location_id,
                                defectiveReason: $item['defective_reason'] ?? null,
                                referenceType: StoreAllocation::class,
                                referenceId: $allocation->id,
                                referenceNumber: $allocation->allocation_number,
                                userId: $userId ?? $allocation->created_by,
                                occurredAt: $allocation->allocated_at
                            );
                        }
                    }

                    // Consolidate movement DTOs by (productId, locationId, condition, movementType) to aggregate quantities
                    $consolidatedMovements = [];
                    foreach ($movementDtos as $dto) {
                        $key = "{$dto->productId}_{$dto->locationId}_{$dto->condition->value}_{$dto->movementType->value}";
                        if (isset($consolidatedMovements[$key])) {
                            $existing = $consolidatedMovements[$key];
                            $consolidatedMovements[$key] = new StockChangeDTO(
                                productId: $existing->productId,
                                locationId: $existing->locationId,
                                quantity: bcadd($existing->quantity, $dto->quantity, 4),
                                movementType: $existing->movementType,
                                referenceType: $existing->referenceType,
                                referenceId: $existing->referenceId,
                                referenceNumber: $existing->referenceNumber,
                                userId: $existing->userId,
                                occurredAt: $existing->occurredAt,
                                condition: $existing->condition
                            );
                        } else {
                            $consolidatedMovements[$key] = $dto;
                        }
                    }

                    // Record all inventory movements
                    $this->stockMovementService->recordMultipleMovements(array_values($consolidatedMovements));

                    app(ActivityLogger::class)->record(
                        module: 'store_allocations',
                        action: 'create',
                        description: "Memproses alokasi penggantian unit toko {$allocation->allocation_number} untuk {$store->code} - {$store->name}",
                        subject: $allocation,
                        properties: [
                            'allocation_number' => $allocation->allocation_number,
                            'store_id' => $store->id,
                            'store_code' => $store->code,
                            'store_name' => $store->name,
                            'technician_user_id' => $data['technician_user_id'] ?? null,
                            'items_count' => count($data['items'] ?? []),
                        ],
                        userId: $userId
                    );

                    return $allocation->fresh([
                        'technician',
                        'location',
                        'store',
                        'creator',
                        'items.product',
                        'items.pulledProduct',
                    ]);
                }, 5);
            } catch (QueryException $e) {
                $isDuplicate = ($e->errorInfo[1] ?? 0) === 1062 && str_contains($e->getMessage(), 'allocation_number');
                $isDeadlock = str_contains(strtolower($e->getMessage()), 'deadlock');

                $attempt++;
                if (($isDuplicate || $isDeadlock) && $attempt < $maxRetries) {
                    usleep(50000); // 50ms backoff

                    continue;
                }

                if ($isDuplicate) {
                    throw new DomainException('Gagal membuat nomor alokasi toko karena tingginya transaksi bersamaan. Silakan coba lagi.', 409);
                }

                throw $e;
            }
        }

        throw new DomainException('Gagal memproses alokasi toko.', 500);
    }
}
