<?php

namespace App\Features\Rma\Actions;

use App\Features\Location\Models\Location;
use App\Features\MonthEnd\Services\PeriodLockService;
use App\Features\Product\Models\Product;
use App\Features\ProductSerial\Models\ProductSerial;
use App\Features\Rma\Enums\RmaItemStatus;
use App\Features\Rma\Enums\RmaStatus;
use App\Features\Rma\Models\VendorRma;
use App\Features\Supplier\Models\Supplier;
use App\Shared\Exceptions\DomainException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateVendorRmaAction
{
    public function __construct(
        private readonly PeriodLockService $periodLockService
    ) {}

    public function execute(array $data, int $userId): VendorRma
    {
        return DB::transaction(function () use ($data, $userId) {
            $supplier = Supplier::find($data['supplier_id']);
            if (! $supplier || ! $supplier->is_active) {
                throw new DomainException('Supplier/Vendor tidak ditemukan atau tidak aktif.', 422);
            }

            $originLocation = Location::find($data['origin_location_id']);
            if (! $originLocation || ! $originLocation->is_active) {
                throw new DomainException('Lokasi asal barang tidak ditemukan atau tidak aktif.', 422);
            }

            $date = Carbon::now()->toDateString();
            $this->periodLockService->ensureDateIsOpen($date, 'membuat dokumen RMA vendor');

            $prefix = 'RMA-'.Carbon::now()->format('Ym').'-';
            $lastRma = VendorRma::where('rma_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $nextSequence = 1;
            if ($lastRma) {
                $lastNum = (int) substr($lastRma->rma_number, -4);
                $nextSequence = $lastNum + 1;
            }

            $rmaNumber = $prefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);

            $rma = VendorRma::create([
                'rma_number' => $rmaNumber,
                'supplier_id' => $data['supplier_id'],
                'origin_location_id' => $data['origin_location_id'],
                'status' => RmaStatus::DRAFT,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::find($item['product_id']);
                if (! $product || ! $product->is_active) {
                    throw new DomainException("Produk ID {$item['product_id']} tidak aktif atau tidak ditemukan.", 422);
                }

                $sn = ! empty($item['serial_number']) ? trim((string) $item['serial_number']) : null;
                $productSerialId = null;

                if ($sn) {
                    $serialObj = ProductSerial::where('serial_number', $sn)->first();
                    if ($serialObj) {
                        $productSerialId = $serialObj->id;
                    }
                }

                $rma->items()->create([
                    'product_id' => $item['product_id'],
                    'product_serial_id' => $productSerialId,
                    'serial_number' => $sn,
                    'quantity' => (int) ($item['quantity'] ?? 1),
                    'fault_description' => $item['fault_description'] ?? null,
                    'status' => RmaItemStatus::PENDING,
                ]);
            }

            return $rma->load(['supplier', 'originLocation', 'items.product', 'items.productSerial', 'creator']);
        });
    }
}
