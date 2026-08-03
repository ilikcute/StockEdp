<?php

namespace App\Features\Reporting\Resources;

use App\Features\Reporting\Helpers\DecimalQuantity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockAdjustmentReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $direction = is_object($this->adjustment?->direction)
            ? $this->adjustment->direction->value
            : (string) ($this->adjustment?->direction ?? 'INCREASE');

        $reasonCode = is_object($this->adjustment?->reason_code)
            ? $this->adjustment->reason_code->value
            : (string) ($this->adjustment?->reason_code ?? 'OTHER');

        return [
            'item_id' => $this->id,
            'adjustment_id' => $this->stock_adjustment_id,
            'adjustment_number' => $this->adjustment?->adjustment_number ?? '-',
            'document_date' => $this->adjustment?->adjustment_date?->format('Y-m-d'),
            'posted_at' => $this->adjustment?->posted_at?->format('Y-m-d H:i:s'),
            'location' => [
                'id' => $this->adjustment?->location_id,
                'name' => $this->adjustment?->location?->name ?? '-',
            ],
            'direction' => $direction,
            'reason_code' => $reasonCode,
            'notes' => $this->adjustment?->notes,
            'product' => [
                'id' => $this->product_id,
                'sku' => $this->product?->sku ?? '-',
                'barcode' => $this->product?->barcode,
                'name' => $this->product?->name ?? '-',
                'category_name' => $this->product?->category?->name ?? '-',
                'unit_name' => $this->product?->unit?->name ?? '-',
            ],
            'quantity' => DecimalQuantity::normalize($this->quantity),
            'created_by' => $this->adjustment?->creator ? [
                'id' => $this->adjustment->creator->id,
                'name' => $this->adjustment->creator->name,
            ] : null,
            'posted_by' => $this->adjustment?->poster ? [
                'id' => $this->adjustment->poster->id,
                'name' => $this->adjustment->poster->name,
            ] : null,
        ];
    }
}
