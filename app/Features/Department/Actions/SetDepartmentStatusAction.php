<?php

namespace App\Features\Department\Actions;

use App\Features\Department\Models\Department;
use App\Features\Department\Repositories\Contracts\DepartmentRepositoryInterface;

class SetDepartmentStatusAction
{
    public function __construct(
        private DepartmentRepositoryInterface $repository
    ) {}

    public function execute(Department $department, bool $isActive, ?int $userId = null): Department
    {
        $data = [
            'is_active' => $isActive,
        ];

        if ($userId) {
            $data['updated_by'] = $userId;
        }

        return $this->repository->update($department, $data);
    }
}
