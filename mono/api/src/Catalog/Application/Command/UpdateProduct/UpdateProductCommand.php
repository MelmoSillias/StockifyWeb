<?php

namespace App\Catalog\Application\Command\UpdateProduct;

final readonly class UpdateProductCommand
{
    public function __construct(
        public string $productId,
        public ?string $name = null,
        public ?string $reference = null,
        public ?string $description = null,
        public ?string $categoryId = null,
    ) {
    }
}
