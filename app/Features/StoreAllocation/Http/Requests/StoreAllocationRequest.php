<?php

namespace App\Features\StoreAllocation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('store_allocations.create');
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'technician_user_id' => ['required', 'integer', 'exists:users,id'],
            'technician_location_id' => ['required', 'integer', 'exists:locations,id'],
            'allocated_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.serial_number' => ['nullable', 'string', 'max:100'],
            'items.*.pulled_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.pulled_quantity' => ['nullable', 'numeric', 'gt:0'],
            'items.*.pulled_serial_number' => ['nullable', 'string', 'max:100'],
            'items.*.defective_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
