<?php

namespace App\Features\StoreAllocation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreAllocationItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product?->name),
            'product_sku' => $this->whenLoaded('product', fn () => $this->product?->sku),
            'quantity' => $this->quantity,
            'serial_number' => $this->serial_number,
            'pulled_product_id' => $this->pulled_product_id,
            'pulled_product_name' => $this->whenLoaded('pulledProduct', fn () => $this->pulledProduct?->name),
            'pulled_product_sku' => $this->whenLoaded('pulledProduct', fn () => $this->pulledProduct?->sku),
            'pulled_quantity' => $this->pulled_quantity,
            'pulled_serial_number' => $this->pulled_serial_number,
            'defective_reason' => $this->defective_reason,
        ];
    }
}
