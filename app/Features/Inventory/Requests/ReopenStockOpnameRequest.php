<?php

namespace App\Features\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReopenStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
