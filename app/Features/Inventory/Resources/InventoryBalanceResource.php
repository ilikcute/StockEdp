<?php

namespace App\Features\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'location_id' => $this->location_id,
            'quantity' => $this->quantity,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'product' => [
                'id' => $this->product->id ?? null,
                'name' => $this->product->name ?? null,
                'sku' => $this->product->sku ?? null,
                'barcode' => $this->product->barcode ?? null,
                'unit_price' => (float) ($this->product->unit_price ?? 0),
            ],
            'total_value' => (float) bcmul((string) $this->quantity, (string) ($this->product->unit_price ?? '0'), 2),
            'location' => [
                'id' => $this->location->id ?? null,
                'name' => $this->location->name ?? null,
                'code' => $this->location->code ?? null,
            ],
        ];
    }
}
