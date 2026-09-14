<?php

namespace App\Features\Reporting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreAllocationReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_allocation_id' => $this->store_allocation_id,
            'allocation_number' => $this->allocation_number,
            'allocated_at' => $this->allocated_at,
            'store_id' => $this->store_id,
            'store_name' => $this->store_name,
            'store_code' => $this->store_code,
            'store_address' => $this->store_address,
            'technician_name' => $this->technician_name,
            'technician_location_name' => $this->technician_location_name,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'unit_price' => isset($this->unit_price) ? (float) $this->unit_price : 0.0,
            'quantity' => (float) $this->quantity,
            'total_value' => isset($this->total_value) ? (float) $this->total_value : 0.0,
            'serial_number' => $this->serial_number,
            'pulled_product_id' => $this->pulled_product_id,
            'pulled_product_name' => $this->pulled_product_name,
            'pulled_product_sku' => $this->pulled_product_sku,
            'pulled_quantity' => $this->pulled_quantity !== null ? (float) $this->pulled_quantity : null,
            'pulled_serial_number' => $this->pulled_serial_number,
            'defective_reason' => $this->defective_reason,
            'notes' => $this->notes,
        ];
    }
}
