<?php

namespace App\Features\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'is_active' => (bool) $this->is_active,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'roles' => $this->roles->map(fn ($role) => [
                'id' => $role->id,
                'code' => $role->code->value,
                'name' => $role->name,
            ]),
            'role_ids' => $this->roles->pluck('id')->values()->all(),
            'locations' => $this->locations->map(fn ($location) => [
                'id' => $location->id,
                'code' => $location->code,
                'name' => $location->name,
            ]),
            'location_ids' => $this->locations->pluck('id')->values()->all(),
        ];
    }
}
