<?php

namespace App\Features\Reporting\Resources;

use App\Features\Inventory\Enums\MovementType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockCardReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $quantityBefore = (string) $this->quantity_before;
        $quantityAfter = (string) $this->quantity_after;
        $delta = bcsub($quantityAfter, $quantityBefore, 4);

        if (bccomp($delta, '0.0000', 4) > 0) {
            $direction = 'IN';
            $quantityIn = $delta;
            $quantityOut = '0.0000';
        } elseif (bccomp($delta, '0.0000', 4) < 0) {
            $direction = 'OUT';
            $quantityIn = '0.0000';
            $quantityOut = bcsub('0.0000', $delta, 4);
        } else {
            $direction = 'NONE';
            $quantityIn = '0.0000';
            $quantityOut = '0.0000';
        }

        $movementTypeLabel = MovementType::tryFrom((string) $this->movement_type)?->label()
            ?? (string) $this->movement_type;

        return [
            'id' => $this->id,
            'movement_sequence' => $this->id,
            'movement_id' => $this->movement_id,
            'occurred_at' => $this->occurred_at,
            'document_date' => $this->occurred_at,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'posted_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'movement_type' => $this->movement_type,
            'movement_type_label' => $movementTypeLabel,
            'direction' => $direction,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'reference_number' => $this->reference_number,
            'counterpart_label' => $this->counterpart_label,
            'counterpart_location_name' => $this->counterpart_location_name,
            'counterpart_location_code' => $this->counterpart_location_code,
            'quantity_in' => $quantityIn,
            'quantity_out' => $quantityOut,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'unit_price' => (float) ($this->product?->unit_price ?? 0),
            'created_by' => $this->creator?->name ?? '-',
        ];
    }
}
