<?php

namespace App\Catalog\Application\Dto;

final readonly class VariantCatalogItemDto
{
    public function __construct(
        public string $id,
        public string $sku,
        public string $label,
        public string $productId,
        public string $productName,
        public ?string $categoryId,
        public ?string $categoryName,
        public string $unitOfMeasureId,
        public string $unitCode,
        public string $unitLabel,
        public string $saleMode,
        public string $available,
        public bool $isLowStock,
        public string $defaultPrice,
        public ?string $averageCost,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'label' => $this->label,
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'unit_of_measure_id' => $this->unitOfMeasureId,
            'unit_code' => $this->unitCode,
            'unit_label' => $this->unitLabel,
            'sale_mode' => $this->saleMode,
            'available' => $this->available,
            'is_low_stock' => $this->isLowStock,
            'default_price' => $this->defaultPrice,
            'average_cost' => $this->averageCost,
        ];
    }
}
