<?php

namespace App\Features\Dashboard\Http\Requests;

use App\Features\Auth\Enums\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

class DashboardFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->hasPermissionTo(PermissionCode::DASHBOARD_VIEW)) {
            return false;
        }

        $locationId = $this->query('location_id');
        if (! empty($locationId)) {
            $allowedLocationIds = $user->getAllowedLocationIds();
            if (! in_array((int) $locationId, $allowedLocationIds, true)) {
                return false;
            }
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'period' => ['nullable', 'string', 'in:today,7d,30d'],
        ];
    }
}
