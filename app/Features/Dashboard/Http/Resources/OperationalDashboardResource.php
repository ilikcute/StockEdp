<?php

namespace App\Features\Dashboard\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OperationalDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'filters' => $this->resource['filters'],
            'inventory_health' => $this->resource['inventory_health'],
            'operational_queue' => $this->resource['operational_queue'],
            'period_activity' => $this->resource['period_activity'],
            'alerts' => $this->resource['alerts'],
            'recent_activity' => $this->resource['recent_activity'],
            'top_issued_products' => $this->resource['top_issued_products'],
            'top_received_products' => $this->resource['top_received_products'],
            'generated_at' => $this->resource['generated_at'],
        ];
    }
}
