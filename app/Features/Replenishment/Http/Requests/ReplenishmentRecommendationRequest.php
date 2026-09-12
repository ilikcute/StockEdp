<?php

namespace App\Features\Replenishment\Http\Requests;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Replenishment\Enums\ReplenishmentPriority;
use App\Features\Replenishment\Enums\ReplenishmentRecommendationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReplenishmentRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->can(PermissionCode::REPLENISHMENT_VIEW->value)) {
            return false;
        }

        $locationId = (int) $this->input('location_id');
        if ($locationId > 0) {
            $allowedLocationIds = $user->getAllowedLocationIds();
            if (! in_array($locationId, $allowedLocationIds, true)) {
                return false;
            }
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'recommendation_type' => [
                'nullable',
                'string',
                Rule::enum(ReplenishmentRecommendationType::class),
            ],
            'priority' => [
                'nullable',
                'string',
                Rule::enum(ReplenishmentPriority::class),
            ],
            'sort_by' => [
                'nullable',
                'string',
                Rule::in([
                    'gross_shortage_quantity',
                    'shortage_quantity',
                    'minimum_stock',
                    'on_hand_quantity',
                    'product_name',
                    'sku',
                ]),
            ],
            'sort_order' => ['nullable', 'string', Rule::in(['asc', 'desc', 'ASC', 'DESC'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
