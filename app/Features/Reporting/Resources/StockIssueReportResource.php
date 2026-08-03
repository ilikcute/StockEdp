<?php

namespace App\Features\Reporting\Resources;

use App\Features\Reporting\Helpers\DecimalQuantity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockIssueReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'item_id' => $this->id,
            'issue_id' => $this->stock_issue_id,
            'issue_number' => $this->issue?->issue_number ?? '-',
            'document_date' => $this->issue?->date?->format('Y-m-d'),
            'posted_at' => $this->movement_posted_at ?? $this->issue?->updated_at?->format('Y-m-d H:i:s'),
            'location' => [
                'id' => $this->location_id,
                'name' => $this->location?->name ?? '-',
            ],
            'purpose' => $this->issue?->purpose ?? '-',
            'notes' => $this->issue?->notes,
            'product' => [
                'id' => $this->product_id,
                'sku' => $this->product?->sku ?? '-',
                'barcode' => $this->product?->barcode,
                'name' => $this->product?->name ?? '-',
                'category_name' => $this->product?->category?->name ?? '-',
                'unit_name' => $this->product?->unit?->name ?? '-',
            ],
            'quantity' => DecimalQuantity::normalize($this->quantity),
            'created_by' => $this->issue?->creator ? [
                'id' => $this->issue->creator->id,
                'name' => $this->issue->creator->name,
            ] : null,
            'posted_by' => $this->issue?->creator ? [
                'id' => $this->issue->creator->id,
                'name' => $this->issue->creator->name,
            ] : null,
        ];
    }
}
