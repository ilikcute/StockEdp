<?php

namespace App\Features\ProductSerial\Services;

use App\Features\Inventory\Enums\StockCondition;
use App\Features\ProductSerial\Enums\SerialMovementType;
use App\Features\ProductSerial\Enums\SerialStatus;
use App\Features\ProductSerial\Models\ProductSerial;
use App\Features\ProductSerial\Models\ProductSerialMovement;
use App\Shared\Exceptions\DomainException;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductSerialService
{
    /**
     * Cari atau daftarkan serial number baru on-the-fly.
     */
    public function findOrCreateSerial(
        string $serialNumber,
        int $productId,
        ?int $locationId = null,
        ?int $storeId = null,
        StockCondition $condition = StockCondition::GOOD,
        SerialStatus $status = SerialStatus::IN_STOCK,
        ?string $notes = null
    ): ProductSerial {
        $trimmed = trim($serialNumber);

        /** @var ProductSerial $serial */
        $serial = ProductSerial::query()->firstOrCreate(
            ['serial_number' => $trimmed],
            [
                'product_id' => $productId,
                'current_location_id' => $locationId,
                'current_store_id' => $storeId,
                'current_condition' => $condition,
                'status' => $status,
                'notes' => $notes,
            ]
        );

        return $serial;
    }

    /**
     * Catat pemasangan unit serial di toko (Store Allocation Install).
     */
    public function recordInstallation(
        string $serialNumber,
        int $productId,
        int $storeId,
        int $technicianLocationId,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $referenceNumber = null,
        ?int $userId = null,
        ?Carbon $occurredAt = null,
        ?string $notes = null
    ): ProductSerial {
        $trimmed = trim($serialNumber);
        $occurredAt = $occurredAt ?? now();
        $userId = $userId ?? Auth::id();

        return DB::transaction(function () use (
            $trimmed,
            $productId,
            $storeId,
            $technicianLocationId,
            $referenceType,
            $referenceId,
            $referenceNumber,
            $userId,
            $occurredAt,
            $notes
        ) {
            /** @var ProductSerial|null $serial */
            $serial = ProductSerial::query()->where('serial_number', $trimmed)->lockForUpdate()->first();

            if (! $serial) {
                // Auto-register on the fly (unit baru pertama kali tercatat)
                $serial = ProductSerial::create([
                    'serial_number' => $trimmed,
                    'product_id' => $productId,
                    'current_location_id' => null,
                    'current_store_id' => $storeId,
                    'current_condition' => StockCondition::GOOD,
                    'status' => SerialStatus::INSTALLED,
                    'notes' => $notes,
                ]);

                ProductSerialMovement::create([
                    'product_serial_id' => $serial->id,
                    'movement_type' => SerialMovementType::STORE_ALLOCATION_INSTALL,
                    'from_location_id' => $technicianLocationId,
                    'to_store_id' => $storeId,
                    'from_condition' => StockCondition::GOOD->value,
                    'to_condition' => StockCondition::GOOD->value,
                    'from_status' => SerialStatus::IN_STOCK->value,
                    'to_status' => SerialStatus::INSTALLED->value,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'reference_number' => $referenceNumber,
                    'user_id' => $userId,
                    'notes' => $notes ?: 'Pemasangan awal di toko (Auto-registered on install)',
                    'occurred_at' => $occurredAt,
                ]);

                return $serial;
            }

            // Validasi keunikan dan kepemilikan
            if ((int) $serial->product_id !== (int) $productId) {
                $productName = $serial->product?->name ?? "ID {$serial->product_id}";
                throw new DomainException("Serial number '{$trimmed}' terdaftar untuk produk {$productName}, bukan untuk produk yang dipilih.", 422);
            }

            if ($serial->status === SerialStatus::INSTALLED && (int) $serial->current_store_id !== (int) $storeId) {
                $storeName = $serial->currentStore?->name ?? "ID {$serial->current_store_id}";
                throw new DomainException("Serial number '{$trimmed}' saat ini masih tercatat terpasang di toko lain ({$storeName}). Harap lakukan penarikan terlebih dahulu.", 422);
            }

            if ($serial->status === SerialStatus::RETURNED_TO_VENDOR) {
                throw new DomainException("Serial number '{$trimmed}' sedang berstatus dikirim ke vendor (RMA) dan belum diterima kembali.", 422);
            }

            $prevStatus = $serial->status;
            $prevCondition = $serial->current_condition;
            $prevLocation = $serial->current_location_id;
            $prevStore = $serial->current_store_id;

            $serial->update([
                'current_location_id' => null,
                'current_store_id' => $storeId,
                'current_condition' => StockCondition::GOOD,
                'status' => SerialStatus::INSTALLED,
                'notes' => $notes ?: $serial->notes,
            ]);

            ProductSerialMovement::create([
                'product_serial_id' => $serial->id,
                'movement_type' => SerialMovementType::STORE_ALLOCATION_INSTALL,
                'from_location_id' => $prevLocation ?? $technicianLocationId,
                'to_location_id' => null,
                'from_store_id' => $prevStore,
                'to_store_id' => $storeId,
                'from_condition' => $prevCondition?->value ?? StockCondition::GOOD->value,
                'to_condition' => StockCondition::GOOD->value,
                'from_status' => $prevStatus?->value ?? SerialStatus::IN_STOCK->value,
                'to_status' => SerialStatus::INSTALLED->value,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_number' => $referenceNumber,
                'user_id' => $userId,
                'notes' => $notes ?: "Pemasangan unit di toko",
                'occurred_at' => $occurredAt,
            ]);

            return $serial;
        });
    }

    /**
     * Catat penarikan unit lama/rusak dari toko ke bagasi teknisi (Store Allocation Pull).
     */
    public function recordPull(
        string $pulledSerialNumber,
        int $pulledProductId,
        int $storeId,
        int $technicianLocationId,
        ?string $defectiveReason = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $referenceNumber = null,
        ?int $userId = null,
        ?Carbon $occurredAt = null
    ): ProductSerial {
        $trimmed = trim($pulledSerialNumber);
        $occurredAt = $occurredAt ?? now();
        $userId = $userId ?? Auth::id();

        return DB::transaction(function () use (
            $trimmed,
            $pulledProductId,
            $storeId,
            $technicianLocationId,
            $defectiveReason,
            $referenceType,
            $referenceId,
            $referenceNumber,
            $userId,
            $occurredAt
        ) {
            /** @var ProductSerial|null $serial */
            $serial = ProductSerial::query()->where('serial_number', $trimmed)->lockForUpdate()->first();

            if (! $serial) {
                // Auto-register on the fly unit lama yang baru pertama kali ditarik
                $serial = ProductSerial::create([
                    'serial_number' => $trimmed,
                    'product_id' => $pulledProductId,
                    'current_location_id' => $technicianLocationId,
                    'current_store_id' => null,
                    'current_condition' => StockCondition::DEFECTIVE,
                    'status' => SerialStatus::DEFECTIVE,
                    'notes' => $defectiveReason ?: 'Penarikan unit rusak dari toko',
                ]);

                ProductSerialMovement::create([
                    'product_serial_id' => $serial->id,
                    'movement_type' => SerialMovementType::STORE_ALLOCATION_PULL,
                    'from_store_id' => $storeId,
                    'to_location_id' => $technicianLocationId,
                    'from_condition' => StockCondition::DEFECTIVE->value,
                    'to_condition' => StockCondition::DEFECTIVE->value,
                    'from_status' => SerialStatus::INSTALLED->value,
                    'to_status' => SerialStatus::DEFECTIVE->value,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'reference_number' => $referenceNumber,
                    'user_id' => $userId,
                    'notes' => $defectiveReason ?: 'Penarikan unit rusak (Auto-registered on pull)',
                    'occurred_at' => $occurredAt,
                ]);

                return $serial;
            }

            // Jika serial sudah ada di database, update status dan lokasi ke teknisi
            $prevStatus = $serial->status;
            $prevCondition = $serial->current_condition;
            $prevStore = $serial->current_store_id ?? $storeId;
            $prevLocation = $serial->current_location_id;

            $serial->update([
                'product_id' => $pulledProductId,
                'current_location_id' => $technicianLocationId,
                'current_store_id' => null,
                'current_condition' => StockCondition::DEFECTIVE,
                'status' => SerialStatus::DEFECTIVE,
                'notes' => $defectiveReason ?: $serial->notes,
            ]);

            ProductSerialMovement::create([
                'product_serial_id' => $serial->id,
                'movement_type' => SerialMovementType::STORE_ALLOCATION_PULL,
                'from_location_id' => $prevLocation,
                'to_location_id' => $technicianLocationId,
                'from_store_id' => $prevStore,
                'to_store_id' => null,
                'from_condition' => $prevCondition?->value ?? StockCondition::DEFECTIVE->value,
                'to_condition' => StockCondition::DEFECTIVE->value,
                'from_status' => $prevStatus?->value ?? SerialStatus::INSTALLED->value,
                'to_status' => SerialStatus::DEFECTIVE->value,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_number' => $referenceNumber,
                'user_id' => $userId,
                'notes' => $defectiveReason ?: 'Penarikan unit rusak dari toko',
                'occurred_at' => $occurredAt,
            ]);

            return $serial;
        });
    }

    /**
     * Catat mutasi/transfer serial antar lokasi gudang/teknisi.
     */
    public function recordTransfer(
        string $serialNumber,
        int $fromLocationId,
        int $toLocationId,
        StockCondition $condition,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $referenceNumber = null,
        ?int $userId = null,
        ?string $notes = null,
        ?Carbon $occurredAt = null
    ): ProductSerial {
        $trimmed = trim($serialNumber);
        $occurredAt = $occurredAt ?? now();
        $userId = $userId ?? Auth::id();

        return DB::transaction(function () use (
            $trimmed,
            $fromLocationId,
            $toLocationId,
            $condition,
            $referenceType,
            $referenceId,
            $referenceNumber,
            $userId,
            $notes,
            $occurredAt
        ) {
            /** @var ProductSerial $serial */
            $serial = ProductSerial::query()->where('serial_number', $trimmed)->lockForUpdate()->firstOrFail();

            $prevStatus = $serial->status;
            $prevCondition = $serial->current_condition;

            $newStatus = $condition === StockCondition::DEFECTIVE ? SerialStatus::DEFECTIVE : SerialStatus::IN_STOCK;

            $serial->update([
                'current_location_id' => $toLocationId,
                'current_store_id' => null,
                'current_condition' => $condition,
                'status' => $newStatus,
            ]);

            ProductSerialMovement::create([
                'product_serial_id' => $serial->id,
                'movement_type' => SerialMovementType::TRANSFER,
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'from_condition' => $prevCondition?->value,
                'to_condition' => $condition->value,
                'from_status' => $prevStatus?->value,
                'to_status' => $newStatus->value,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_number' => $referenceNumber,
                'user_id' => $userId,
                'notes' => $notes ?: 'Transfer fisik antar lokasi',
                'occurred_at' => $occurredAt,
            ]);

            return $serial;
        });
    }

    /**
     * Catat pengiriman serial rusak ke vendor untuk klaim garansi/servis (RMA Dispatch).
     */
    public function recordRmaDispatch(
        string $serialNumber,
        int $productId,
        int $fromLocationId,
        string $referenceType,
        int $referenceId,
        string $referenceNumber,
        ?int $userId = null,
        ?string $notes = null,
        ?Carbon $occurredAt = null
    ): ProductSerial {
        $trimmed = trim($serialNumber);
        $occurredAt = $occurredAt ?? now();
        $userId = $userId ?? Auth::id();

        return DB::transaction(function () use (
            $trimmed,
            $productId,
            $fromLocationId,
            $referenceType,
            $referenceId,
            $referenceNumber,
            $userId,
            $notes,
            $occurredAt
        ) {
            $serial = ProductSerial::query()->where('serial_number', $trimmed)->lockForUpdate()->first();

            if (! $serial) {
                // Auto register unit rusak yang belum tercatat
                $serial = ProductSerial::create([
                    'serial_number' => $trimmed,
                    'product_id' => $productId,
                    'current_location_id' => null,
                    'current_store_id' => null,
                    'current_condition' => StockCondition::DEFECTIVE,
                    'status' => SerialStatus::RETURNED_TO_VENDOR,
                    'notes' => $notes ?: 'Dikirim ke vendor untuk klaim garansi/servis',
                ]);

                ProductSerialMovement::create([
                    'product_serial_id' => $serial->id,
                    'movement_type' => SerialMovementType::RMA_DISPATCH,
                    'from_location_id' => $fromLocationId,
                    'to_location_id' => null,
                    'from_condition' => StockCondition::DEFECTIVE->value,
                    'to_condition' => StockCondition::DEFECTIVE->value,
                    'from_status' => SerialStatus::DEFECTIVE->value,
                    'to_status' => SerialStatus::RETURNED_TO_VENDOR->value,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'reference_number' => $referenceNumber,
                    'user_id' => $userId,
                    'notes' => $notes ?: 'Klaim RMA Vendor (Auto-registered on dispatch)',
                    'occurred_at' => $occurredAt,
                ]);

                return $serial;
            }

            $prevStatus = $serial->status;
            $prevCondition = $serial->current_condition;
            $prevLocation = $serial->current_location_id;

            $serial->update([
                'current_location_id' => null,
                'current_store_id' => null,
                'current_condition' => StockCondition::DEFECTIVE,
                'status' => SerialStatus::RETURNED_TO_VENDOR,
                'notes' => $notes ?: $serial->notes,
            ]);

            ProductSerialMovement::create([
                'product_serial_id' => $serial->id,
                'movement_type' => SerialMovementType::RMA_DISPATCH,
                'from_location_id' => $prevLocation ?? $fromLocationId,
                'to_location_id' => null,
                'from_condition' => $prevCondition?->value ?? StockCondition::DEFECTIVE->value,
                'to_condition' => StockCondition::DEFECTIVE->value,
                'from_status' => $prevStatus?->value ?? SerialStatus::DEFECTIVE->value,
                'to_status' => SerialStatus::RETURNED_TO_VENDOR->value,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_number' => $referenceNumber,
                'user_id' => $userId,
                'notes' => $notes ?: 'Dikirim ke vendor (RMA Dispatch)',
                'occurred_at' => $occurredAt,
            ]);

            return $serial;
        });
    }

    /**
     * Catat penerimaan kembali serial dari vendor (RMA Return) dengan kondisi GOOD di gudang.
     */
    public function recordRmaReturn(
        string $serialNumber,
        int $productId,
        int $toLocationId,
        string $referenceType,
        int $referenceId,
        string $referenceNumber,
        ?int $userId = null,
        ?string $notes = null,
        ?Carbon $occurredAt = null
    ): ProductSerial {
        $trimmed = trim($serialNumber);
        $occurredAt = $occurredAt ?? now();
        $userId = $userId ?? Auth::id();

        return DB::transaction(function () use (
            $trimmed,
            $productId,
            $toLocationId,
            $referenceType,
            $referenceId,
            $referenceNumber,
            $userId,
            $notes,
            $occurredAt
        ) {
            $serial = ProductSerial::query()->where('serial_number', $trimmed)->lockForUpdate()->first();

            if (! $serial) {
                $serial = ProductSerial::create([
                    'serial_number' => $trimmed,
                    'product_id' => $productId,
                    'current_location_id' => $toLocationId,
                    'current_store_id' => null,
                    'current_condition' => StockCondition::GOOD,
                    'status' => SerialStatus::IN_STOCK,
                    'notes' => $notes ?: 'Diterima kembali dari servis vendor (GA_SERVICED)',
                ]);

                ProductSerialMovement::create([
                    'product_serial_id' => $serial->id,
                    'movement_type' => SerialMovementType::RMA_RETURN,
                    'from_location_id' => null,
                    'to_location_id' => $toLocationId,
                    'from_condition' => StockCondition::DEFECTIVE->value,
                    'to_condition' => StockCondition::GOOD->value,
                    'from_status' => SerialStatus::RETURNED_TO_VENDOR->value,
                    'to_status' => SerialStatus::IN_STOCK->value,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'reference_number' => $referenceNumber,
                    'user_id' => $userId,
                    'notes' => $notes ?: 'Penerimaan Servis Vendor (Auto-registered on return)',
                    'occurred_at' => $occurredAt,
                ]);

                return $serial;
            }

            $prevStatus = $serial->status;
            $prevCondition = $serial->current_condition;

            $serial->update([
                'current_location_id' => $toLocationId,
                'current_store_id' => null,
                'current_condition' => StockCondition::GOOD,
                'status' => SerialStatus::IN_STOCK,
                'notes' => $notes ?: $serial->notes,
            ]);

            ProductSerialMovement::create([
                'product_serial_id' => $serial->id,
                'movement_type' => SerialMovementType::RMA_RETURN,
                'from_location_id' => null,
                'to_location_id' => $toLocationId,
                'from_condition' => $prevCondition?->value ?? StockCondition::DEFECTIVE->value,
                'to_condition' => StockCondition::GOOD->value,
                'from_status' => $prevStatus?->value ?? SerialStatus::RETURNED_TO_VENDOR->value,
                'to_status' => SerialStatus::IN_STOCK->value,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_number' => $referenceNumber,
                'user_id' => $userId,
                'notes' => $notes ?: 'Unit selesai diservis vendor & masuk stok bagus',
                'occurred_at' => $occurredAt,
            ]);

            return $serial;
        });
    }

    /**
     * Filter & paginasi daftar serial number.
     */
    public function paginateSerials(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ProductSerial::query()->with([
            'product.unit',
            'product.category',
            'currentLocation',
            'currentStore',
        ]);

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('serial_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                    ->orWhereHas('currentStore', fn ($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('currentLocation', fn ($lq) => $lq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('current_location_id', $filters['location_id']);
        }

        if (! empty($filters['store_id'])) {
            $query->where('current_store_id', $filters['store_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['condition'])) {
            $query->where('current_condition', $filters['condition']);
        }

        return $query->orderByDesc('updated_at')->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Lookup satu serial number spesifik beserta riwayat movement.
     */
    public function getSerialWithHistory(string|int $idOrNumber): ?ProductSerial
    {
        $query = ProductSerial::query()->with([
            'product.unit',
            'product.category',
            'currentLocation',
            'currentStore',
            'movements.fromLocation',
            'movements.toLocation',
            'movements.fromStore',
            'movements.toStore',
            'movements.user',
        ]);

        if (is_numeric($idOrNumber)) {
            return $query->where('id', $idOrNumber)->orWhere('serial_number', (string) $idOrNumber)->first();
        }

        return $query->where('serial_number', trim($idOrNumber))->first();
    }
}
