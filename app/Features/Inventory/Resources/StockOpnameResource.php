<?php

namespace App\Features\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockOpnameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'opname_number' => $this->opname_number,
            'location_id' => $this->location_id,
            'location_name' => $this->whenLoaded('location', fn () => $this->location->name),
            'opname_date' => $this->opname_date?->format('Y-m-d'),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'notes' => $this->notes,
            'cancel_reason' => $this->cancel_reason,
            'items' => StockOpnameItemResource::collection($this->whenLoaded('items')),
            'reopen_logs' => $this->whenLoaded('reopenLogs', function () {
                return $this->reopenLogs->map(fn ($log) => [
                    'id' => $log->id,
                    'reopened_by' => $log->reopener?->name,
                    'reason' => $log->reason,
                    'reopened_at' => $log->reopened_at?->format('Y-m-d H:i:s'),
                ]);
            }),
            'started_by' => $this->whenLoaded('starter', fn () => $this->starter?->name),
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'completed_by' => $this->whenLoaded('completer', fn () => $this->completer?->name),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'posted_by' => $this->whenLoaded('poster', fn () => $this->poster?->name),
            'posted_at' => $this->posted_at?->format('Y-m-d H:i:s'),
            'canceled_by' => $this->whenLoaded('canceler', fn () => $this->canceler?->name),
            'canceled_at' => $this->canceled_at?->format('Y-m-d H:i:s'),
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'creator_id' => $this->created_by,
            'updated_by' => $this->whenLoaded('updater', fn () => $this->updater?->name),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'abilities' => [
                'can_update' => $user ? $user->can('update', $this->resource) : false,
                'can_start' => $user ? $user->can('start', $this->resource) : false,
                'can_count' => $user ? $user->can('count', $this->resource) : false,
                'can_add_unexpected' => $user ? $user->can('addUnexpected', $this->resource) : false,
                'can_complete' => $user ? $user->can('complete', $this->resource) : false,
                'can_reopen' => $user ? $user->can('reopen', $this->resource) : false,
                'can_post' => $user ? $user->can('post', $this->resource) : false,
                'can_cancel' => $user ? $user->can('cancel', $this->resource) : false,
            ],
        ];
    }
}
