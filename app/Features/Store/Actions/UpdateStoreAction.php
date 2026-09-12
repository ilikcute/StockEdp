<?php

namespace App\Features\Store\Actions;

use App\Features\Store\Models\Store;
use App\Features\Store\Repositories\Contracts\StoreRepositoryInterface;

class UpdateStoreAction
{
    public function __construct(
        private StoreRepositoryInterface $repository
    ) {}

    public function execute(Store $store, array $data, ?int $userId = null): Store
    {
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        if ($userId) {
            $data['updated_by'] = $userId;
        }

        return $this->repository->update($store, $data);
    }
}
