<?php

namespace App\Features\User\Requests;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo(PermissionCode::USERS_MANAGE) ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user') instanceof User
            ? $this->route('user')->id
            : $this->route('user');

        return [
            'name' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9_.-]+$/',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => ['integer', 'exists:locations,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('username')) {
            $this->merge(['username' => strtolower(trim((string) $this->username))]);
        }
        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->email))]);
        }
        if ($this->has('name')) {
            $this->merge(['name' => trim((string) $this->name)]);
        }
    }
}
