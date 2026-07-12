<?php

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Entity\ProductVariant;
use Symfony\Component\Uid\Uuid;

interface ProductVariantRepositoryInterface
{
    public function save(ProductVariant $variant, bool $flush = true): void;

    public function remove(ProductVariant $variant): void;

    public function findById(Uuid $id): ?ProductVariant;

    /** @return list<ProductVariant> */
    public function findByProduct(Product $product): array;

    /**
     * All variants with product, category and unit eagerly loaded.
     *
     * @return list<ProductVariant>
     */
    public function findAllWithCatalogRelations(): array;

    /** @return list<ProductVariant> */
    public function findWithAlertThreshold(): array;
}
