<?php

namespace App\Features\Reporting\Resources;

use App\Features\Reporting\Helpers\DecimalQuantity;
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
            'on_hand_quantity' => DecimalQuantity::normalize($this->on_hand_quantity),
            'minimum_stock' => DecimalQuantity::normalize($this->minimum_stock),
            'shortage_quantity' => DecimalQuantity::normalize($this->shortage_quantity),
            'is_product_active' => (bool) $this->is_product_active,
        ];
    }
}
