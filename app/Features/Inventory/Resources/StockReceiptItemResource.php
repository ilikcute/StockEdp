<?php

namespace App\Features\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockReceiptItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'location_id' => $this->location_id,
            'quantity' => $this->quantity,

            'product' => [
                'id' => $this->product->id ?? null,
                'name' => $this->product->name ?? null,
                'sku' => $this->product->sku ?? null,
                'unit' => [
                    'name' => $this->product->unit->name ?? null,
                    'symbol' => $this->product->unit->symbol ?? null,
                ],
            ],

            'location' => [
                'id' => $this->location->id ?? null,
                'name' => $this->location->name ?? null,
                'code' => $this->location->code ?? null,
            ],
        ];
    }
}
