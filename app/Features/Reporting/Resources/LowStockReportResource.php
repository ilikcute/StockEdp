<?php

namespace App\Features\Reporting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LowStockReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->product_id,
            'product_sku' => $this->sku ?? '-',
            'product_barcode' => $this->barcode,
            'product_name' => $this->product_name ?? '-',
            'category_name' => $this->category_name ?? '-',
            'unit_name' => $this->unit_name ?? '-',
            'on_hand_quantity' => number_format((float) $this->on_hand_quantity, 4, '.', ''),
            'minimum_stock' => number_format((float) $this->minimum_stock, 4, '.', ''),
            'shortage_quantity' => number_format((float) $this->shortage_quantity, 4, '.', ''),
            'is_product_active' => (bool) $this->is_product_active,
        ];
    }
}
