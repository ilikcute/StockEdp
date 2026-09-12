<?php

namespace App\Features\Replenishment\Http\Requests;

use App\Features\Auth\Enums\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

class ReplenishmentFilterOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->can(PermissionCode::REPLENISHMENT_VIEW->value);
    }

    public function rules(): array
    {
        return [];
    }
}
