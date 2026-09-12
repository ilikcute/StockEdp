<?php

namespace App\Features\Store\Actions;

use App\Features\Store\Models\Store;
use App\Features\Store\Repositories\Contracts\StoreRepositoryInterface;

class CreateStoreAction
{
    public function __construct(
        private StoreRepositoryInterface $repository
    ) {}

    public function execute(array $data, ?int $userId = null): Store
    {
        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $data['is_active'] ?? true;

        if ($userId) {
            $data['created_by'] = $userId;
        }

        return $this->repository->create($data);
    }
}
