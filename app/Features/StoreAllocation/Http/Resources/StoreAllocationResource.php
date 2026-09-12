<?php

namespace App\Features\StoreAllocation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreAllocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'allocation_number' => $this->allocation_number,
            'technician_user_id' => $this->technician_user_id,
            'technician_name' => $this->whenLoaded('technician', fn () => $this->technician?->name),
            'technician_location_id' => $this->technician_location_id,
            'technician_location_name' => $this->whenLoaded('location', fn () => $this->location?->name),
            'store_id' => $this->store_id,
            'store_name' => $this->whenLoaded('store', fn () => $this->store?->name),
            'store_code' => $this->whenLoaded('store', fn () => $this->store?->code),
            'allocated_at' => $this->allocated_at?->format('Y-m-d'),
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'creator_name' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'created_at' => $this->created_at?->toIso8601String(),
            'items' => StoreAllocationItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
