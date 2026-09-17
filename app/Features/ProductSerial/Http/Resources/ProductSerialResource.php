<?php

namespace App\Features\ProductSerial\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductSerialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'serial_number' => $this->serial_number,
            'product' => [
                'id' => $this->product?->id,
                'sku' => $this->product?->sku,
                'name' => $this->product?->name,
                'unit' => $this->product?->unit?->name,
                'category' => $this->product?->category?->name,
            ],
            'current_location' => $this->currentLocation ? [
                'id' => $this->currentLocation->id,
                'code' => $this->currentLocation->code,
                'name' => $this->currentLocation->name,
                'type' => $this->currentLocation->type,
            ] : null,
            'current_store' => $this->currentStore ? [
                'id' => $this->currentStore->id,
                'code' => $this->currentStore->code,
                'name' => $this->currentStore->name,
            ] : null,
            'current_condition' => $this->current_condition?->value,
            'current_condition_label' => $this->current_condition?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'movements' => ProductSerialMovementResource::collection($this->whenLoaded('movements')),
        ];
    }
}
