<?php

namespace App\Features\Supplier\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canViewSensitive = $request->user()?->can('suppliers.view') ?? false;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'contact_person' => $this->when($canViewSensitive, $this->contact_person),
            'phone' => $this->when($canViewSensitive, $this->phone),
            'email' => $this->when($canViewSensitive, $this->email),
            'address' => $this->when($canViewSensitive, $this->address),
            'tax_number' => $this->when($canViewSensitive, $this->tax_number),
            'is_active' => $this->is_active,
            'created_by' => $this->created_by,
            'created_by_name' => $this->whenLoaded('createdBy', fn () => $this->createdBy?->name),
            'updated_by' => $this->updated_by,
            'updated_by_name' => $this->whenLoaded('updatedBy', fn () => $this->updatedBy?->name),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
