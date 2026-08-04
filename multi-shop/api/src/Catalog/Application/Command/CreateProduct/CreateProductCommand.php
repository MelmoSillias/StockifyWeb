<?php

namespace App\Catalog\Application\Command\CreateProduct;

final readonly class CreateProductCommand
{
    public function __construct(
        public string $name,
        public ?string $reference = null,
        public ?string $description = null,
        public ?string $categoryId = null,
    ) {
    }
}
