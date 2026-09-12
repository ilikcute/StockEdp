<?php

namespace App\Features\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $items = $this->input('items', []);
        if (! is_array($items) || empty($items)) {
            return true; // Let validation fail
        }

        $requestedLocations = collect($items)->pluck('location_id')->filter()->unique()->toArray();
        if (empty($requestedLocations)) {
            return true; // Let validation fail
        }

        $allowedLocations = $this->user()->getAllowedLocationIds();
        $unauthorized = array_diff($requestedLocations, $allowedLocations);

        return empty($unauthorized);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where('is_active', true),
            ],
            'date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'items.*.location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where('is_active', true),
            ],
            'items.*.quantity' => 'required|numeric|gt:0',
        ];
    }
}
