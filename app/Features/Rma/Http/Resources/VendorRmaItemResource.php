<?php

namespace App\Features\Rma\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorRmaItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor_rma_id' => $this->vendor_rma_id,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'sku' => $this->product->sku,
                'unit' => $this->product->unit?->name,
            ]),
            'product_serial_id' => $this->product_serial_id,
            'serial_number' => $this->serial_number,
            'quantity' => (int) $this->quantity,
            'fault_description' => $this->fault_description,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status,
            'status_label' => $this->status instanceof \App\Features\Rma\Enums\RmaItemStatus ? $this->status->label() : (string) $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
