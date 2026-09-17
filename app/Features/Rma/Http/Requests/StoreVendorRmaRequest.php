<?php

namespace App\Features\Rma\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorRmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where('is_active', true),
            ],
            'origin_location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where('is_active', true),
            ],
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'items.*.serial_number' => 'nullable|string|max:100',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.fault_description' => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $items = $this->input('items', []);
            $serials = [];

            foreach ($items as $idx => $item) {
                $sn = ! empty($item['serial_number']) ? trim((string) $item['serial_number']) : null;
                if ($sn) {
                    if (in_array($sn, $serials, true)) {
                        $v->errors()->add("items.{$idx}.serial_number", "Serial number '{$sn}' diinput lebih dari satu kali dalam dokumen RMA ini.");
                    } else {
                        $serials[] = $sn;
                    }
                }
            }
        });
    }
}
