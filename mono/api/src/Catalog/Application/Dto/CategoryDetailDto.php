<?php

namespace App\Catalog\Application\Dto;

final readonly class CategoryDetailDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $parentId,
        public int $sortOrder,
        public string $status,
        public int $productCount,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'parent_id' => $this->parentId,
            'sort_order' => $this->sortOrder,
            'status' => $this->status,
            'product_count' => $this->productCount,
        ];
    }
}
