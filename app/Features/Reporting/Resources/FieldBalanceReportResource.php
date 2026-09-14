<?php

namespace App\Features\Reporting\Resources;

use App\Features\Reporting\Helpers\DecimalQuantity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FieldBalanceReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? ($this->location_id.'-'.$this->product_id),
            'technician_id' => $this->technician_id,
            'technician_name' => $this->technician_name ?? '-',
            'technician_email' => $this->technician_email ?? '-',
            'location_id' => $this->location_id,
            'location_code' => $this->location_code ?? '-',
            'location_name' => $this->location_name ?? '-',
            'product_id' => $this->product_id,
            'product_sku' => $this->product_sku ?? '-',
            'product_name' => $this->product_name ?? '-',
            'category_name' => $this->category_name ?? '-',
            'unit_name' => $this->unit_name ?? '-',
            'unit_price' => (float) ($this->unit_price ?? 0),
            'good_quantity' => DecimalQuantity::normalize((string) ($this->good_quantity ?? '0')),
            'defective_quantity' => DecimalQuantity::normalize((string) ($this->defective_quantity ?? '0')),
            'total_quantity' => DecimalQuantity::normalize((string) ($this->total_quantity ?? '0')),
            'total_value' => (float) ($this->total_value ?? ((float) ($this->total_quantity ?? 0) * (float) ($this->unit_price ?? 0))),
        ];
    }
}
