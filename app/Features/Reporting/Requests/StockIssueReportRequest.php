<?php

namespace App\Features\Reporting\Requests;

use App\Features\Auth\Enums\PermissionCode;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class StockIssueReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can(PermissionCode::REPORTS_STOCK_ISSUES_VIEW->value);
    }

    public function rules(): array
    {
        return [
            'location_id' => 'nullable|integer|exists:locations,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'unit_id' => 'nullable|integer|exists:units,id',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'search' => 'nullable|string|max:100',
            'sort_by' => 'nullable|string|in:posted_at,document_date,issue_number,id',
            'sort_order' => 'nullable|string|in:asc,desc,ASC,DESC',
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
                }
            }
        });
    }
}
