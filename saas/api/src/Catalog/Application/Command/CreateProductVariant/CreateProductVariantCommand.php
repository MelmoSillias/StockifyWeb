<?php

namespace App\Catalog\Application\Command\CreateProductVariant;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Enum\SaleMode;

final readonly class CreateProductVariantCommand
{
    public function __construct(
        public Product $product,
        public string $sku,
        public string $unitOfMeasureId,
        public SaleMode $saleMode,
        public ?string $defaultPrice = null,
        public ?string $alertThreshold = null,
    ) {
    }
}
