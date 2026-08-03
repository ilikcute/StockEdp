<?php

namespace App\Features\Reporting\Resources;

use App\Features\Reporting\Helpers\DecimalQuantity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockReceiptReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'item_id' => $this->id,
            'receipt_id' => $this->stock_receipt_id,
            'receipt_number' => $this->receipt?->receipt_number ?? '-',
            'document_date' => $this->receipt?->date?->format('Y-m-d'),
            'posted_at' => $this->movement_posted_at ?? $this->receipt?->updated_at?->format('Y-m-d H:i:s'),
            'location' => [
                'id' => $this->location_id,
                'name' => $this->location?->name ?? '-',
            ],
            'supplier' => [
                'id' => $this->receipt?->supplier_id,
                'name' => $this->receipt?->supplier?->name ?? '-',
            ],
            'product' => [
                'id' => $this->product_id,
                'sku' => $this->product?->sku ?? '-',
                'barcode' => $this->product?->barcode,
                'name' => $this->product?->name ?? '-',
                'category_name' => $this->product?->category?->name ?? '-',
                'unit_name' => $this->product?->unit?->name ?? '-',
            ],
            'quantity' => DecimalQuantity::normalize($this->quantity),
            'created_by' => $this->receipt?->creator ? [
                'id' => $this->receipt->creator->id,
                'name' => $this->receipt->creator->name,
            ] : null,
            'posted_by' => $this->receipt?->creator ? [
                'id' => $this->receipt->creator->id,
                'name' => $this->receipt->creator->name,
            ] : null,
            'notes' => $this->receipt?->notes,
        ];
    }
}
