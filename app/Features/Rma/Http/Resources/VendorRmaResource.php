<?php

namespace App\Features\Rma\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorRmaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rma_number' => $this->rma_number,
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
                'contact_person' => $this->supplier->contact_person,
                'phone' => $this->supplier->phone,
            ]),
            'origin_location_id' => $this->origin_location_id,
            'origin_location' => $this->whenLoaded('originLocation', fn () => [
                'id' => $this->originLocation->id,
                'name' => $this->originLocation->name,
                'code' => $this->originLocation->code,
                'type' => $this->originLocation->type,
            ]),
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status,
            'status_label' => $this->status instanceof \App\Features\Rma\Enums\RmaStatus ? $this->status->label() : (string) $this->status,
            'dispatch_date' => $this->dispatch_date?->toDateString(),
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'username' => $this->creator->username,
            ]),
            'items_count' => $this->items()->count(),
            'items' => VendorRmaItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
