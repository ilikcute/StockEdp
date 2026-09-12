<?php

namespace App\Features\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductBarcodeLookupRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $code = $this->input('barcode') ?? $this->input('sku') ?? $this->input('code');
        if (is_string($code)) {
            $this->merge([
                'barcode' => trim($code),
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
