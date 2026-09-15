<?php

namespace App\Features\Department\Actions;

use App\Features\Department\Models\Department;
use App\Features\Department\Repositories\Contracts\DepartmentRepositoryInterface;

class UpdateDepartmentAction
{
    public function __construct(
        private DepartmentRepositoryInterface $repository
    ) {}

    public function execute(Department $department, array $data, ?int $userId = null): Department
    {
        if (isset($data['code'])) {
            $data['code'] = strtoupper(trim($data['code']));
        }
        if (isset($data['name'])) {
            $data['name'] = trim($data['name']);
        }

        if ($userId) {
            $data['updated_by'] = $userId;
        }

        return $this->repository->update($department, $data);
    }
}
