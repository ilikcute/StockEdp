<?php

namespace App\Features\Reporting\Requests;

use App\Features\Auth\Enums\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

class LowStockReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can(PermissionCode::REPORTS_LOW_STOCK_VIEW->value);
    }

    public function rules(): array
    {
        return [
            'location_id' => 'required|integer|exists:locations,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'unit_id' => 'nullable|integer|exists:units,id',
            'include_inactive' => 'nullable|in:0,1',
            'search' => 'nullable|string|max:100',
            'sort_by' => 'nullable|string|in:shortage_quantity,minimum_stock,on_hand_quantity,product_name,sku',
            'sort_order' => 'nullable|string|in:asc,desc,ASC,DESC',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
