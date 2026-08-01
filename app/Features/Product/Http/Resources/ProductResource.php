<?php

namespace App\Features\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'unit_id' => $this->unit_id,
            'category_name' => $this->whenLoaded('category', fn () => $this->category?->name),
            'unit_name' => $this->whenLoaded('unit', fn () => $this->unit?->name),
            'unit_abbreviation' => $this->whenLoaded('unit', fn () => $this->unit?->abbreviation),
            'minimum_stock' => $this->minimum_stock,
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
