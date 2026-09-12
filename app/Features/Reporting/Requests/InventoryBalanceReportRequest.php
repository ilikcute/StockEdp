<?php

namespace App\Features\Reporting\Requests;

use App\Features\Auth\Enums\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

class InventoryBalanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can(PermissionCode::REPORTS_INVENTORY_BALANCE_VIEW->value);
    }

    public function rules(): array
    {
        return [
            'product_id' => 'nullable|integer|exists:products,id',
            'location_id' => 'nullable|integer|exists:locations,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'unit_id' => 'nullable|integer|exists:units,id',
            'is_active' => 'nullable|in:0,1,true,false',
            'positive_stock' => 'nullable|in:0,1',
            'zero_stock' => 'nullable|in:0,1',
            'frozen_location' => 'nullable|in:0,1',
            'search' => 'nullable|string|max:100',
            'sort_by' => 'nullable|string|in:id,product_id,location_id,quantity,created_at',
            'sort_order' => 'nullable|string|in:asc,desc,ASC,DESC',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
