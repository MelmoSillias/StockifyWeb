<?php

namespace App\Inventory\Application\Dto;

final readonly class StockAlertDetailDto
{
    public function __construct(
        public string $variantId,
        public string $sku,
        public string $variantLabel,
        public string $productId,
        public string $productName,
        public ?string $categoryId,
        public ?string $categoryName,
        public string $available,
        public string $alertThreshold,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'variant_id' => $this->variantId,
            'sku' => $this->sku,
            'variant_label' => $this->variantLabel,
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'available' => $this->available,
            'alert_threshold' => $this->alertThreshold,
        ];
    }
}
