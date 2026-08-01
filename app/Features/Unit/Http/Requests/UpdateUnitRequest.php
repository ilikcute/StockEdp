<?php

namespace App\Features\Unit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('units.update') ?? false;
    }

    public function rules(): array
    {
        $unitId = $this->route('unit')?->id ?? $this->route('unit');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('units', 'code')->ignore($unitId)],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim($this->code))]);
        }
    }
}
