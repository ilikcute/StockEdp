<?php

namespace App\Features\Reporting\Resources;

use App\Features\Reporting\Helpers\DecimalQuantity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockOpnameReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $signedVariance = (string) ($this->variance_quantity ?? '0.0000');
        $comp = bccomp($signedVariance, '0.0000', 4);

        if ($comp > 0) {
            $movementDirection = 'OPNAME_IN';
        } elseif ($comp < 0) {
            $movementDirection = 'OPNAME_OUT';
        } else {
            $movementDirection = 'NONE';
        }

        return [
            'item_id' => $this->id,
            'opname_id' => $this->stock_opname_id,
            'opname_number' => $this->opname?->opname_number ?? '-',
            'document_date' => $this->opname?->opname_date?->format('Y-m-d'),
            'posted_at' => $this->opname?->posted_at?->format('Y-m-d H:i:s'),
            'location' => [
                'id' => $this->opname?->location_id,
                'name' => $this->opname?->location?->name ?? '-',
            ],
            'product' => [
                'id' => $this->product_id,
                'sku' => $this->product?->sku ?? '-',
                'barcode' => $this->product?->barcode,
                'name' => $this->product?->name ?? '-',
                'category_name' => $this->product?->category?->name ?? '-',
                'unit_name' => $this->product?->unit?->name ?? '-',
            ],
            'snapshot_quantity' => DecimalQuantity::normalize($this->snapshot_quantity),
            'counted_quantity' => DecimalQuantity::normalize($this->counted_quantity),
            'signed_variance' => DecimalQuantity::normalize($signedVariance),
            'movement_direction' => $movementDirection,
            'is_unexpected' => (bool) $this->is_unexpected,
            'last_counted_by' => $this->counter ? [
                'id' => $this->counter->id,
                'name' => $this->counter->name,
            ] : null,
            'created_by' => $this->opname?->creator ? [
                'id' => $this->opname->creator->id,
                'name' => $this->opname->creator->name,
            ] : null,
            'completed_by' => $this->opname?->completer ? [
                'id' => $this->opname->completer->id,
                'name' => $this->opname->completer->name,
            ] : null,
            'posted_by' => $this->opname?->poster ? [
                'id' => $this->opname->poster->id,
                'name' => $this->opname->poster->name,
            ] : null,
        ];
    }
}
