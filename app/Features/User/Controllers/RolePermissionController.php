<?php

namespace App\Features\User\Controllers;

use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\User\Resources\PermissionResource;
use App\Features\User\Resources\RoleResource;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class RolePermissionController extends Controller
{
    public function roles(): JsonResponse
    {
        $roles = Role::with(['permissions'])->withCount('users')->get();

        return ApiResponse::success(
            data: RoleResource::collection($roles),
            message: 'Daftar peran & hak akses berhasil diambil.'
        );
    }

    public function permissions(): JsonResponse
    {
        $permissions = Permission::all()->groupBy('group');

        $groupedData = [];
        foreach ($permissions as $group => $items) {
            $groupedData[$group] = PermissionResource::collection($items);
        }

        return ApiResponse::success(
            data: $groupedData,
            message: 'Daftar seluruh hak akses sistem berhasil diambil.'
        );
    }
}
