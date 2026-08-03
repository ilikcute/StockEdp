<?php

namespace App\Features\Reporting\Requests;

use App\Features\Auth\Enums\PermissionCode;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class StockCardReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can(PermissionCode::REPORTS_STOCK_CARD_VIEW->value);
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'location_id' => 'required|integer|exists:locations,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('start_date') && $this->filled('end_date')) {
                try {
                    $start = CarbonImmutable::parse($this->input('start_date'), 'Asia/Jakarta');
                    $end = CarbonImmutable::parse($this->input('end_date'), 'Asia/Jakarta');

                    if ($start->diffInDays($end) > 366) {
                        $validator->errors()->add('end_date', 'Rentang tanggal tidak boleh melebihi 366 hari.');
                    }
                } catch (\Throwable) {
                    // Invalid date format already handled by date_format rule
                }
            }
        });
    }
}
