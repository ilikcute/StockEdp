<?php

namespace App\Features\ProductSerial\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductSerialMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'movement_type' => $this->movement_type?->value,
            'movement_type_label' => $this->movement_type?->label(),
            'from_location' => $this->fromLocation ? [
                'id' => $this->fromLocation->id,
                'name' => $this->fromLocation->name,
                'code' => $this->fromLocation->code,
            ] : null,
            'to_location' => $this->toLocation ? [
                'id' => $this->toLocation->id,
                'name' => $this->toLocation->name,
                'code' => $this->toLocation->code,
            ] : null,
            'from_store' => $this->fromStore ? [
                'id' => $this->fromStore->id,
                'name' => $this->fromStore->name,
                'code' => $this->fromStore->code,
            ] : null,
            'to_store' => $this->toStore ? [
                'id' => $this->toStore->id,
                'name' => $this->toStore->name,
                'code' => $this->toStore->code,
            ] : null,
            'from_condition' => $this->from_condition?->value,
            'from_condition_label' => $this->from_condition?->label(),
            'to_condition' => $this->to_condition?->value,
            'to_condition_label' => $this->to_condition?->label(),
            'from_status' => $this->from_status?->value,
            'from_status_label' => $this->from_status?->label(),
            'to_status' => $this->to_status?->value,
            'to_status_label' => $this->to_status?->label(),
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'reference_number' => $this->reference_number,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
            ] : null,
            'notes' => $this->notes,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
