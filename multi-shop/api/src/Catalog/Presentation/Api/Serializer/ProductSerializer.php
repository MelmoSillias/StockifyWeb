<?php

namespace App\Catalog\Presentation\Api\Serializer;

use App\Catalog\Domain\Entity\Product;

final class ProductSerializer
{
    /** @return array<string, mixed> */
    public function serialize(Product $product): array
    {
        return [
            'id' => (string) $product->getId(),
            'name' => $product->getName(),
            'reference' => $product->getReference(),
            'description' => $product->getDescription(),
            'category_id' => $product->getCategory() ? (string) $product->getCategory()->getId() : null,
            'status' => $product->getStatus()->value,
        ];
    }

    /** @param list<Product> $products */
    public function serializeList(array $products): array
    {
        return array_map($this->serialize(...), $products);
    }
}
