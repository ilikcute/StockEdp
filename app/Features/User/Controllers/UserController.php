<?php

namespace App\Features\User\Controllers;

use App\Features\Auth\Models\User;
use App\Features\User\Requests\UserCreateRequest;
use App\Features\User\Requests\UserIndexRequest;
use App\Features\User\Requests\UserStatusRequest;
use App\Features\User\Requests\UserUpdateRequest;
use App\Features\User\Resources\UserResource;
use App\Features\User\Services\UserService;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function index(UserIndexRequest $request): JsonResponse
    {
        $paginator = $this->userService->listUsers($request->validated());

        return ApiResponse::success(
            data: UserResource::collection($paginator->items()),
            message: 'Daftar pengguna berhasil diambil.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ]
        );
    }

    public function formOptions(): JsonResponse
    {
        $options = $this->userService->getFormOptions();

        return ApiResponse::success(
            data: $options,
            message: 'Opsi formulir pengguna berhasil diambil.'
        );
    }

    public function show(User $user): JsonResponse
    {
        $user->loadMissing(['roles', 'locations']);

        return ApiResponse::success(
            data: new UserResource($user),
            message: 'Detail pengguna berhasil diambil.'
        );
    }

    public function store(UserCreateRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());

        return ApiResponse::success(
            data: new UserResource($user),
            message: 'Pengguna baru berhasil ditambahkan.',
            status: 201
        );
    }

    public function update(UserUpdateRequest $request, User $user): JsonResponse
    {
        $updatedUser = $this->userService->updateUser(
            $user,
            $request->validated(),
            $request->user()
        );

        return ApiResponse::success(
            data: new UserResource($updatedUser),
            message: 'Data pengguna berhasil diperbarui.'
        );
    }

    public function updateStatus(UserStatusRequest $request, User $user): JsonResponse
    {
        $updatedUser = $this->userService->updateUserStatus(
            $user,
            (bool) $request->validated('is_active'),
            $request->user()
        );

        $statusText = $updatedUser->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return ApiResponse::success(
            data: new UserResource($updatedUser),
            message: "Status pengguna berhasil {$statusText}."
        );
    }
}
