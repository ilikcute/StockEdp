<?php

namespace App\Features\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['sometimes', 'array'],
            'items.*.item_id' => ['required', 'integer', 'exists:stock_transfer_items,id'],
            'items.*.received_quantity' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<int, string|float|int>
     */
    public function receivedQuantities(): array
    {
        $map = [];

        foreach ($this->validated('items') ?? [] as $item) {
            $map[(int) $item['item_id']] = $item['received_quantity'];
        }

        return $map;
    }
}
