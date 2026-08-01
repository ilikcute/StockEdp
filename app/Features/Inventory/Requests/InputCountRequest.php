<?php

namespace App\Features\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InputCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'counted_quantity' => ['required', 'numeric', 'min:0'],
            'expected_version' => ['nullable', 'integer', 'min:0'],
            'item_notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
