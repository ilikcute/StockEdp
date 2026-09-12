<?php

namespace App\Features\Auth\Http\Resources;

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
            'is_active' => $this->is_active,
            'roles' => $this->roles->pluck('code')->map(fn ($code) => $code->value),
            'permissions' => $this->roles->flatMap(fn ($role) => $role->permissions->pluck('code')->map(fn ($code) => $code->value))->unique()->values()->all(),
        ];
    }
}
