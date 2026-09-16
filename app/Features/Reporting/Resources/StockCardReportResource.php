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
            $movementQuantity = $quantityIn;
        } elseif (bccomp($delta, '0.0000', 4) < 0) {
            $direction = 'OUT';
            $quantityIn = '0.0000';
            $quantityOut = bcsub('0.0000', $delta, 4);
            $movementQuantity = $quantityOut;
        } else {
            $direction = 'NONE';
            $quantityIn = '0.0000';
            $quantityOut = '0.0000';
            $movementQuantity = '0.0000';
        }

        $movementTypeLabel = MovementType::tryFrom((string) $this->movement_type)?->label()
            ?? (string) $this->movement_type;

        $unitPrice = (float) ($this->product?->unit_price ?? 0);
        $totalAmount = (float) bcmul((string) $unitPrice, $movementQuantity, 2);

        return [
            'id' => $this->id,
            'movement_sequence' => $this->id,
            'movement_id' => $this->movement_id,
            'occurred_at' => $this->occurred_at,
            'document_date' => $this->occurred_at,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'posted_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'movement_posted_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'movement_type' => $this->movement_type,
            'movement_type_label' => $movementTypeLabel,
            'direction' => $direction,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'reference_number' => $this->reference_number,
            'counterpart_label' => $this->counterpart_label,
            'counterpart_location_name' => $this->counterpart_location_name,
            'counterpart_location_code' => $this->counterpart_location_code,
            'store_id' => $this->store_id ?? null,
            'store_code' => $this->store_code ?? null,
            'store_name' => $this->store_name ?? null,
            'quantity_in' => $quantityIn,
            'quantity_out' => $quantityOut,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'unit_price' => $unitPrice,
            'total_amount' => $totalAmount,
            'created_by' => $this->creator?->name ?? '-',
        ];
    }
}
