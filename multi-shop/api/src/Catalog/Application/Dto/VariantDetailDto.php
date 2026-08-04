<?php

namespace App\Catalog\Application\Dto;

final readonly class VariantDetailDto
{
    public function __construct(
        public string $id,
        public string $productId,
        public string $sku,
        public string $unitOfMeasureId,
        public string $unitCode,
        public string $unitLabel,
        public string $saleMode,
        public string $defaultPrice,
        public ?string $alertThreshold,
        public string $status,
        public string $available,
        public int $lotCount,
        public bool $isLowStock,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->productId,
            'sku' => $this->sku,
            'unit_of_measure_id' => $this->unitOfMeasureId,
            'unit_code' => $this->unitCode,
            'unit_label' => $this->unitLabel,
            'sale_mode' => $this->saleMode,
            'default_price' => $this->defaultPrice,
            'alert_threshold' => $this->alertThreshold,
            'status' => $this->status,
            'available' => $this->available,
            'lot_count' => $this->lotCount,
            'is_low_stock' => $this->isLowStock,
        ];
    }
}
