<?php

namespace App\Features\Inventory\Requests;

use App\Features\Inventory\Enums\MovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StockMovementRequest extends FormRequest
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
            'movement_type' => ['nullable', new Enum(MovementType::class)],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'search' => 'nullable|string|max:100',
            'sort_by' => 'nullable|string|in:id,occurred_at,created_at,quantity',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
