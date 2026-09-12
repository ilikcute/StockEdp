<?php

namespace App\Features\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'supplier_id' => $this->supplier_id,
            'status' => $this->status,
            'date' => $this->date ? $this->date->format('Y-m-d') : null,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'supplier' => [
                'id' => $this->supplier->id ?? null,
                'name' => $this->supplier->name ?? null,
                'code' => $this->supplier->code ?? null,
            ],

            'creator' => [
                'id' => $this->creator->id ?? null,
                'name' => $this->creator->name ?? null,
            ],

            'items' => StockReceiptItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
