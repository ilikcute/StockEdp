<?php

namespace App\Features\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockIssueRequest extends FormRequest
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
            'purpose' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
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
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
