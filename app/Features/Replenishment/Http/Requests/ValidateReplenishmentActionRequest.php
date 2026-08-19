<?php

namespace App\Features\Replenishment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateReplenishmentActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('replenishment.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'target_location_id' => ['required', 'integer', 'exists:locations,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.source_location_id' => [
                'required',
                'integer',
                'exists:locations,id',
                'different:target_location_id',
            ],
            'items.*.requested_quantity' => [
                'required',
                'regex:/^\d+(\.\d{1,4})?$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_numeric($value) || bccomp((string) $value, '0.0000', 4) <= 0) {
                        $fail('Kuantitas yang diminta harus bernilai lebih dari 0.0000.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'target_location_id.required' => 'Lokasi tujuan wajib dipilih.',
            'target_location_id.exists' => 'Lokasi tujuan tidak ditemukan.',
            'items.required' => 'Daftar item rekomendasi wajib disertakan.',
            'items.min' => 'Minimal satu item rekomendasi harus disertakan.',
            'items.*.product_id.required' => 'ID produk wajib diisi.',
            'items.*.product_id.exists' => 'Produk tidak ditemukan.',
            'items.*.source_location_id.required' => 'Lokasi asal wajib diisi.',
            'items.*.source_location_id.exists' => 'Lokasi asal tidak ditemukan.',
            'items.*.source_location_id.different' => 'Lokasi asal tidak boleh sama dengan lokasi tujuan.',
            'items.*.requested_quantity.required' => 'Kuantitas transfer wajib diisi.',
            'items.*.requested_quantity.regex' => 'Kuantitas transfer harus berupa bilangan desimal maksimal 4 digit di belakang koma.',
        ];
    }
}
