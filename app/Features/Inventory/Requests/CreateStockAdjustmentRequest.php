<?php

namespace App\Features\Inventory\Requests;

use App\Features\Inventory\Enums\AdjustmentReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $locationId = $this->input('location_id');
        if (! $locationId) {
            return true;
        }

        $allowedLocations = $this->user()->getAllowedLocationIds();

        return in_array((int) $locationId, $allowedLocations, true);
    }

    protected function prepareForValidation(): void
    {
        // Trim whitespace from notes if string
        if (is_string($this->notes)) {
            $this->merge([
                'notes' => trim($this->notes),
            ]);
        }
    }

    public function rules(): array
    {
        $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');

        return [
            'location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where('is_active', true),
            ],
            'adjustment_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                "before_or_equal:{$today}",
            ],
            'direction' => [
                'required',
                'string',
                Rule::in(['INCREASE', 'DECREASE']),
            ],
            'reason_code' => [
                'required',
                'string',
                Rule::in(AdjustmentReason::values()),
                function ($attribute, $value, $fail) {
                    $direction = $this->input('direction');
                    if ($direction && $value) {
                        $reasonEnum = AdjustmentReason::tryFrom($value);
                        if ($reasonEnum && ! $reasonEnum->isCompatibleWith($direction)) {
                            $fail("Alasan adjustment '{$reasonEnum->label()}' tidak kompatibel dengan arah {$direction}.");
                        }
                    }
                },
            ],
            'notes' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $reasonCode = $this->input('reason_code');
                    if ($reasonCode === AdjustmentReason::OTHER->value) {
                        if (empty($value) || trim($value) === '') {
                            $fail('Catatan wajib diisi jika alasan penyesuaian adalah Lain-lain.');
                        }
                    }
                },
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('is_active', true),
                'distinct',
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.item_notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'adjustment_date.before_or_equal' => 'Tanggal adjustment tidak boleh berada di masa depan.',
            'items.*.product_id.distinct' => 'Produk tidak boleh duplikat dalam satu dokumen adjustment.',
            'items.*.quantity.gt' => 'Kuantitas item harus lebih besar dari nol.',
        ];
    }
}
