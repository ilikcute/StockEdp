<?php

namespace App\Features\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductBarcodeLookupRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('barcode') && is_string($this->barcode)) {
            $this->merge([
                'barcode' => trim($this->barcode),
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('products.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'barcode' => ['required', 'string', 'max:100'],
        ];
    }
}
