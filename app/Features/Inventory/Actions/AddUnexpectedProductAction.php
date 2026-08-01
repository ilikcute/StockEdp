<?php

namespace App\Features\Inventory\Actions;

use App\Features\Inventory\Models\StockOpname;
use App\Features\Inventory\Models\StockOpnameCountLog;
use App\Features\Inventory\Models\StockOpnameItem;
use App\Features\Inventory\Services\InventoryFreezeService;
use App\Features\Product\Models\Product;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class AddUnexpectedProductAction
{
    public function __construct(
        private readonly InventoryFreezeService $freezeService
    ) {}

    public function execute(StockOpname $opname, array $data, int $userId): StockOpnameItem
    {
        return DB::transaction(function () use ($opname, $data, $userId) {
            $lockedOpname = StockOpname::where('id', $opname->id)->lockForUpdate()->first();

            if (! $lockedOpname->isInProgress()) {
                throw new DomainException('Penambahan produk tak terduga hanya dapat dilakukan saat IN_PROGRESS.', 409, ['code' => 'INVALID_STATUS_TRANSITION']);
            }

            $this->freezeService->lockAndValidateLocations([$lockedOpname->location_id], $lockedOpname->id);

            $product = Product::find($data['product_id']);
            if (! $product || ! $product->is_active) {
                throw new DomainException('Produk tidak ditemukan atau tidak aktif.', 422);
            }

            $exists = $lockedOpname->items()->where('product_id', $product->id)->exists();
            if ($exists) {
                throw new DomainException('Produk sudah terdaftar dalam dokumen stock opname ini.', 409, ['code' => 'DUPLICATE_PRODUCT']);
            }

            $countedQuantity = isset($data['counted_quantity']) ? (string) $data['counted_quantity'] : null;
            if ($countedQuantity !== null && bccomp($countedQuantity, '0.0000', 4) < 0) {
                throw new DomainException('Kuantitas fisik tidak boleh negatif.', 422);
            }

            $version = $countedQuantity !== null ? 1 : 0;

            $item = StockOpnameItem::create([
                'stock_opname_id' => $lockedOpname->id,
                'product_id' => $product->id,
                'snapshot_quantity' => '0.0000',
                'counted_quantity' => $countedQuantity,
                'variance_quantity' => null,
                'count_version' => $version,
                'counted_by' => $countedQuantity !== null ? $userId : null,
                'counted_at' => $countedQuantity !== null ? now() : null,
                'item_notes' => isset($data['item_notes']) ? trim($data['item_notes']) : null,
                'is_unexpected' => true,
            ]);

            if ($countedQuantity !== null) {
                StockOpnameCountLog::create([
                    'stock_opname_item_id' => $item->id,
                    'user_id' => $userId,
                    'previous_quantity' => null,
                    'new_quantity' => $countedQuantity,
                    'count_version' => 1,
                    'created_at' => now(),
                ]);
            }

            return $item->fresh(['product.unit', 'counter']);
        });
    }
}
