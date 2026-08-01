<?php

namespace App\Features\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $originLocationId = $this->input('origin_location_id');
        if (! $originLocationId) {
            return true;
        }

        $allowedLocations = $this->user()->getAllowedLocationIds();

        return in_array($originLocationId, $allowedLocations);
    }

    public function rules(): array
    {
        return [
            'origin_location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where('is_active', true),
            ],
            'destination_location_id' => [
                'required',
                'integer',
                'different:origin_location_id',
                Rule::exists('locations', 'id')->where('is_active', true),
            ],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('is_active', true),
                'distinct',
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
