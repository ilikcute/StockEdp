<?php

namespace App\Features\Replenishment\DTOs;

class ReplenishmentFilterData
{
    public function __construct(
        public readonly int $locationId,
        public readonly ?string $search = null,
        public readonly ?int $categoryId = null,
        public readonly ?int $unitId = null,
        public readonly ?string $recommendationType = null,
        public readonly ?string $priority = null,
        public readonly string $sortBy = 'gross_shortage_quantity',
        public readonly string $sortOrder = 'desc',
        public readonly int $perPage = 15,
        public readonly int $page = 1,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            locationId: (int) ($data['location_id'] ?? 0),
            search: ! empty($data['search']) ? trim((string) $data['search']) : null,
            categoryId: ! empty($data['category_id']) ? (int) $data['category_id'] : null,
            unitId: ! empty($data['unit_id']) ? (int) $data['unit_id'] : null,
            recommendationType: ! empty($data['recommendation_type']) ? trim((string) $data['recommendation_type']) : null,
            priority: ! empty($data['priority']) ? trim((string) $data['priority']) : null,
            sortBy: ! empty($data['sort_by']) ? trim((string) $data['sort_by']) : 'gross_shortage_quantity',
            sortOrder: strtolower((string) ($data['sort_order'] ?? 'desc')) === 'asc' ? 'asc' : 'desc',
            perPage: max(1, min(100, (int) ($data['per_page'] ?? 15))),
            page: max(1, (int) ($data['page'] ?? 1)),
        );
    }
}
