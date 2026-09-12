<?php

namespace App\Features\Reporting\Requests;

use App\Features\Auth\Enums\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

class InventoryMovementReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && (
            $this->user()->can(PermissionCode::REPORTS_INVENTORY_MOVEMENT_VIEW->value) ||
            $this->user()->can(PermissionCode::REPORTS_VIEW->value) ||
            $this->user()->can(PermissionCode::DASHBOARD_VIEW->value)
        );
    }

    public function rules(): array
    {
        return [
            'type' => 'nullable|string|in:slow-moving,fast-moving',
            'period' => 'nullable|integer|in:30,60,90,120,180,365',
            'location_id' => 'nullable|integer|exists:locations,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'unit_id' => 'nullable|integer|exists:units,id',
            'search' => 'nullable|string|max:100',
            'sort_by' => 'nullable|string|in:days_since_last_movement,velocity_score,total_outbound_quantity,average_daily_outbound,outbound_movement_count,movement_days,current_stock,last_movement_at,product_name,sku',
            'sort_order' => 'nullable|string|in:asc,desc,ASC,DESC',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }
}
