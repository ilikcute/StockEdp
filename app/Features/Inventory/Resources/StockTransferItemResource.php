<?php

namespace App\Features\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $unitPrice = $this->relationLoaded('product') && $this->product ? (float) ($this->product->unit_price ?? 0) : 0;
        $subtotal = $this->relationLoaded('product') && $this->product
            ? (float) bcmul((string) ($this->product->unit_price ?? '0'), (string) $this->quantity, 2)
            : 0;

        return [
            'id' => $this->id,
            'stock_transfer_id' => $this->stock_transfer_id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product->name),
            'product_sku' => $this->whenLoaded('product', fn () => $this->product->sku),
            'product_unit_price' => $unitPrice,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'sku' => $this->product->sku,
                'unit_price' => (float) ($this->product->unit_price ?? 0),
                'unit' => $this->product->relationLoaded('unit') && $this->product->unit ? [
                    'name' => $this->product->unit->name,
                    'symbol' => $this->product->unit->symbol,
                ] : null,
            ]),
            'quantity' => $this->quantity,
        ];
    }
}
