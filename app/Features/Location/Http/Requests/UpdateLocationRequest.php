<?php

namespace App\Features\Location\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('locations.update');
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('locations')->ignore($this->route('location')),
            ],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['sometimes', 'required', 'string', Rule::in(['MAIN_WAREHOUSE', 'FIELD_PERSONNEL', 'DAMAGED_STORAGE'])],
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'required_if:type,FIELD_PERSONNEL'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper($this->code),
            ]);
        }
    }
}
