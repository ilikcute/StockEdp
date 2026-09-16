<?php

namespace App\Features\Reporting\Requests;

use App\Features\Auth\Enums\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

class StoreAllocationReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can(PermissionCode::REPORTS_STORE_ALLOCATIONS_VIEW->value);
    }

    public function rules(): array
    {
        return [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'store_id' => 'nullable|integer|exists:stores,id',
            'technician_id' => 'nullable|integer|exists:users,id',
            'technician_location_id' => 'nullable|integer|exists:locations,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'search' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
            'format' => 'nullable|string|in:csv,xlsx,CSV,XLSX',
        ];
    }
}
