<?php

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Entity\ProductVariant;
use Symfony\Component\Uid\Uuid;

interface ProductVariantRepositoryInterface
{
    public function save(ProductVariant $variant, bool $flush = true): void;

    public function remove(ProductVariant $variant): void;

    /** @return list<ProductVariant> */
    public function findByProduct(Product $product): array;

    public function findForShop(Uuid $shopId, Uuid $variantId): ?ProductVariant;

    /** @return list<ProductVariant> */
    public function findWithAlertThresholdByShop(Uuid $shopId): array;
}
