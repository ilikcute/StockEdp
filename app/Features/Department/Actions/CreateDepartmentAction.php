<?php

namespace App\Features\Department\Actions;

use App\Features\Department\Models\Department;
use App\Features\Department\Repositories\Contracts\DepartmentRepositoryInterface;

class CreateDepartmentAction
{
    public function __construct(
        private DepartmentRepositoryInterface $repository
    ) {}

    public function execute(array $data, ?int $userId = null): Department
    {
        $data['code'] = strtoupper(trim($data['code']));
        $data['name'] = trim($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;

        if ($userId) {
            $data['created_by'] = $userId;
        }

        return $this->repository->create($data);
    }
}
