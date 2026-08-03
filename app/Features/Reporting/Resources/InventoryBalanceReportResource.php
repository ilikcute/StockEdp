<?php

namespace App\Features\Reporting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryBalanceReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $minStockFormatted = number_format((float) ($this->product?->minimum_stock ?? 0), 4, '.', '');
        $onHand = (string) $this->quantity;
        $isBelowMin = bccomp($minStockFormatted, '0.0000', 4) > 0 && bccomp($onHand, $minStockFormatted, 4) < 0;

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_sku' => $this->product?->sku ?? '-',
            'product_barcode' => $this->product?->barcode,
            'product_name' => $this->product?->name ?? '-',
            'category_name' => $this->product?->category?->name ?? '-',
            'unit_name' => $this->product?->unit?->name ?? '-',
            'location_id' => $this->location_id,
            'location_name' => $this->location?->name ?? '-',
            'on_hand_quantity' => $onHand,
            'available_quantity' => $onHand,
            'minimum_stock' => $minStockFormatted,
            'is_below_minimum' => $isBelowMin,
            'is_product_active' => (bool) ($this->product?->is_active ?? false),
            'is_location_frozen' => (bool) ($this->location?->operationLock?->is_frozen ?? false),
        ];
    }
}
