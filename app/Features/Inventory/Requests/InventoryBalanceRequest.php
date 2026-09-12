<?php

namespace App\Features\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'nullable|integer|exists:products,id',
            'location_id' => 'nullable|integer|exists:locations,id',
            'search' => 'nullable|string|max:100',
            'sort_by' => 'nullable|string|in:id,quantity,product_id,location_id,created_at',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
