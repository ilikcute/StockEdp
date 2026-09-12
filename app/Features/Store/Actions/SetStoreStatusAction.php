<?php

namespace App\Features\Store\Actions;

use App\Features\Store\Models\Store;
use App\Features\Store\Repositories\Contracts\StoreRepositoryInterface;

class SetStoreStatusAction
{
    public function __construct(
        private StoreRepositoryInterface $repository
    ) {}

    public function execute(Store $store, bool $isActive, ?int $userId = null): Store
    {
        $data = ['is_active' => $isActive];

        if ($userId) {
            $data['updated_by'] = $userId;
        }

        return $this->repository->update($store, $data);
    }
}
