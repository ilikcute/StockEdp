<?php

namespace App\Features\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockAdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'adjustment_number' => $this->adjustment_number,
            'location_id' => $this->location_id,
            'location_name' => $this->whenLoaded('location', fn () => $this->location->name),
            'adjustment_date' => $this->adjustment_date?->format('Y-m-d'),
            'direction' => $this->direction,
            'reason_code' => $this->reason_code?->value,
            'reason_label' => $this->reason_code?->label(),
            'notes' => $this->notes,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'items' => StockAdjustmentItemResource::collection($this->whenLoaded('items')),
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator->name),
            'creator_id' => $this->created_by,
            'updated_by' => $this->whenLoaded('updater', fn () => $this->updater?->name),
            'posted_by' => $this->whenLoaded('poster', fn () => $this->poster?->name),
            'canceled_by' => $this->whenLoaded('canceler', fn () => $this->canceler?->name),
            'posted_at' => $this->posted_at?->format('Y-m-d H:i:s'),
            'canceled_at' => $this->canceled_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'abilities' => [
                'can_update' => $user ? $user->can('update', $this->resource) : false,
                'can_post' => $user ? $user->can('post', $this->resource) : false,
                'can_cancel' => $user ? $user->can('cancel', $this->resource) : false,
            ],
        ];
    }
}
