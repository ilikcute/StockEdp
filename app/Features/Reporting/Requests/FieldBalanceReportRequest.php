<?php

namespace App\Features\Reporting\Requests;

use App\Features\Auth\Enums\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

class FieldBalanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can(PermissionCode::REPORTS_FIELD_BALANCES_VIEW->value);
    }

    public function rules(): array
    {
        return [
            'technician_id' => 'nullable|integer|exists:users,id',
            'location_id' => 'nullable|integer|exists:locations,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'search' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
            'format' => 'nullable|string|in:csv,xlsx,CSV,XLSX',
        ];
    }
}
