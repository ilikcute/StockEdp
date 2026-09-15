<?php

namespace App\Features\MonthEnd\Http\Requests;

use App\Features\Auth\Enums\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

class ReopenPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::MONTH_END_REOPEN->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'reopen_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reopen_reason.required' => 'Alasan pembukaan kembali periode (re-open) wajib diisi untuk keperluan audit.',
            'reopen_reason.min' => 'Alasan pembukaan kembali minimal 5 karakter.',
        ];
    }
}
