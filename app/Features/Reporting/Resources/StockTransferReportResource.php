<?php

namespace App\Features\Reporting\Resources;

use App\Features\Reporting\Helpers\DecimalQuantity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = is_object($this->transfer?->status)
            ? $this->transfer->status->value
            : (string) ($this->transfer?->status ?? 'SENT');

        $isInTransit = ($status === 'SENT');

        $sentAt = $this->transfer?->sent_at;
        $receivedAt = $this->transfer?->received_at;

        $transitDuration = null;
        if ($sentAt) {
            if ($isInTransit) {
                $transitDuration = now()->diffInSeconds($sentAt);
            } elseif ($receivedAt) {
                $transitDuration = $receivedAt->diffInSeconds($sentAt);
            }
        }

        return [
            'item_id' => $this->id,
            'transfer_id' => $this->stock_transfer_id,
            'transfer_number' => $this->transfer?->transfer_number ?? '-',
            'document_date' => $this->transfer?->transfer_date?->format('Y-m-d'),
            'status' => $status,
            'origin_location' => [
                'id' => $this->transfer?->origin_location_id,
                'code' => $this->transfer?->originLocation?->code ?? '-',
                'name' => $this->transfer?->originLocation?->name ?? '-',
            ],
            'destination_location' => [
                'id' => $this->transfer?->destination_location_id,
                'code' => $this->transfer?->destinationLocation?->code ?? '-',
                'name' => $this->transfer?->destinationLocation?->name ?? '-',
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
            'sent_by' => $this->transfer?->sender ? [
                'id' => $this->transfer->sender->id,
                'name' => $this->transfer->sender->name,
            ] : null,
            'sent_at' => $sentAt?->format('Y-m-d H:i:s'),
            'received_by' => $this->transfer?->receiver ? [
                'id' => $this->transfer->receiver->id,
                'name' => $this->transfer->receiver->name,
            ] : null,
            'received_at' => $receivedAt?->format('Y-m-d H:i:s'),
            'is_in_transit' => $isInTransit,
            'transit_duration_seconds' => $transitDuration,
        ];
    }
}
