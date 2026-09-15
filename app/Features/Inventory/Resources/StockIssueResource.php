<?php

namespace App\Features\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockIssueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'issue_number' => $this->issue_number,
            'purpose' => $this->purpose,
            'department_id' => $this->department_id,
            'department' => $this->department ? [
                'id' => $this->department->id,
                'code' => $this->department->code,
                'name' => $this->department->name,
            ] : null,
            'status' => $this->status->value,
            'date' => $this->date ? $this->date->format('Y-m-d') : null,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'items' => StockIssueItemResource::collection($this->whenLoaded('items')),
            'creator' => [
                'id' => $this->creator->id ?? null,
                'name' => $this->creator->name ?? null,
            ],
        ];
    }
}
