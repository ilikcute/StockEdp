<?php

namespace App\Features\Inventory\Resources;

use App\Features\Inventory\Enums\OpnameStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockOpnameItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->opname?->status;

        // Blind Count Guard: Sembunyikan snapshot_quantity & variance_quantity saat IN_PROGRESS
        $isBlindCount = ($status === OpnameStatus::IN_PROGRESS);

        return [
            'id' => $this->id,
            'stock_opname_id' => $this->stock_opname_id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product->name),
            'product_sku' => $this->whenLoaded('product', fn () => $this->product->sku),
            'unit_symbol' => $this->whenLoaded('product', fn () => $this->product->unit?->symbol ?? $this->product->unit?->name),
            'snapshot_quantity' => $isBlindCount ? null : $this->snapshot_quantity,
            'counted_quantity' => $this->counted_quantity,
            'variance_quantity' => $isBlindCount ? null : $this->variance_quantity,
            'is_counted' => $this->counted_quantity !== null,
            'count_version' => $this->count_version,
            'counted_by' => $this->whenLoaded('counter', fn () => $this->counter?->name),
            'counted_at' => $this->counted_at?->format('Y-m-d H:i:s'),
            'item_notes' => $this->item_notes,
            'is_unexpected' => $this->is_unexpected,
        ];
    }
}
