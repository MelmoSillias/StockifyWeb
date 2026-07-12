<?php

namespace App\Inventory\Application\Dto;

final readonly class StockMovementDetailDto
{
    /**
     * @param list<array{lot_id: string, quantity: string}> $allocations
     */
    public function __construct(
        public string $id,
        public string $variantId,
        public string $variantLabel,
        public string $sku,
        public string $productId,
        public string $productName,
        public ?string $categoryId,
        public ?string $categoryName,
        public string $type,
        public string $direction,
        public string $quantity,
        public string $occurredAt,
        public array $allocations,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'variant_id' => $this->variantId,
            'variant_label' => $this->variantLabel,
            'sku' => $this->sku,
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'type' => $this->type,
            'direction' => $this->direction,
            'quantity' => $this->quantity,
            'occurred_at' => $this->occurredAt,
            'allocations' => $this->allocations,
        ];
    }
}
