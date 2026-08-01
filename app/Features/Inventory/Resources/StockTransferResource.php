<?php

namespace App\Features\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transfer_number' => $this->transfer_number,
            'origin_location_id' => $this->origin_location_id,
            'destination_location_id' => $this->destination_location_id,
            'origin_location_name' => $this->whenLoaded('originLocation', fn () => $this->originLocation->name),
            'destination_location_name' => $this->whenLoaded('destinationLocation', fn () => $this->destinationLocation->name),
            'status' => $this->status,
            'transfer_date' => $this->transfer_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'items' => StockTransferItemResource::collection($this->whenLoaded('items')),
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator->name),
            'sent_at' => $this->sent_at?->format('Y-m-d H:i:s'),
            'received_at' => $this->received_at?->format('Y-m-d H:i:s'),
            'canceled_at' => $this->canceled_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
