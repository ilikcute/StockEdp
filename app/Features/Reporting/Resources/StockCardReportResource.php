<?php

namespace App\Features\Reporting\Resources;

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

        return [
            'id' => $this->id,
            'movement_sequence' => $this->id,
            'movement_id' => $this->movement_id,
            'occurred_at' => $this->occurred_at,
            'document_date' => $this->occurred_at,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'posted_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'movement_type' => $this->movement_type,
            'direction' => $direction,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'reference_number' => $this->reference_number,
            'quantity_in' => $quantityIn,
            'quantity_out' => $quantityOut,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'created_by' => $this->creator?->name ?? '-',
        ];
    }
}
