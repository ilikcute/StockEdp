<?php

namespace App\Features\Reporting\Requests;

use App\Features\Auth\Enums\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

class ReportSupplierFilterOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        return $user->can(PermissionCode::REPORTS_STOCK_RECEIPTS_VIEW->value)
            || $user->can(PermissionCode::REPORTS_VIEW->value)
            || $user->can(PermissionCode::SUPPLIERS_VIEW->value);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}
