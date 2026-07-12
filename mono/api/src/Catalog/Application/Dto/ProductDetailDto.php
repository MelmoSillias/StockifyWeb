<?php

namespace App\Catalog\Application\Dto;

final readonly class ProductDetailDto
{
    /**
     * @param list<VariantDetailDto> $variants
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $reference,
        public ?string $description,
        public ?string $categoryId,
        public ?string $categoryName,
        public string $status,
        public int $variantCount,
        public bool $hasLowStock,
        public array $variants,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'reference' => $this->reference,
            'description' => $this->description,
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'status' => $this->status,
            'variant_count' => $this->variantCount,
            'has_low_stock' => $this->hasLowStock,
            'variants' => array_map(
                static fn (VariantDetailDto $variant) => $variant->toArray(),
                $this->variants,
            ),
        ];
    }
}
