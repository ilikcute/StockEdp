<?php

namespace App\Features\MonthEnd\Http\Requests;

use App\Features\Auth\Enums\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

class ClosePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::MONTH_END_CLOSE->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2020', 'max:2099'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'force' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'year.required' => 'Tahun penutupan buku wajib dipilih.',
            'month.required' => 'Bulan penutupan buku wajib dipilih.',
            'month.min' => 'Bulan harus antara 1 sampai 12.',
            'month.max' => 'Bulan harus antara 1 sampai 12.',
        ];
    }
}
