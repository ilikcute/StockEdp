<?php

namespace App\Features\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddUnexpectedProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'counted_quantity' => ['nullable', 'numeric', 'min:0'],
            'item_notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
