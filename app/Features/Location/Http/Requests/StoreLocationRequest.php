<?php

namespace App\Features\Location\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('locations.create');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:locations,code'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', Rule::in(['MAIN_WAREHOUSE', 'FIELD_PERSONNEL', 'DAMAGED_STORAGE'])],
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'required_if:type,FIELD_PERSONNEL'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function prepareForValidation()
    {
        $merge = [];
        if ($this->has('code')) {
            $merge['code'] = strtoupper($this->code);
        }
        if (! $this->has('type') || empty($this->type)) {
            $merge['type'] = 'MAIN_WAREHOUSE';
        }
        if (! empty($merge)) {
            $this->merge($merge);
        }
    }
}
