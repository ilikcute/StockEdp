<?php

namespace App\Features\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code->value,
            'name' => $this->name,
            'description' => $this->description,
            'users_count' => $this->whenCounted('users'),
            'permissions' => $this->permissions->map(fn ($perm) => [
                'id' => $perm->id,
                'code' => $perm->code->value,
                'name' => $perm->name,
                'group' => $perm->group,
            ]),
        ];
    }
}
