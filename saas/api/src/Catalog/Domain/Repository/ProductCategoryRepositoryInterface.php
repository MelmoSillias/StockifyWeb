<?php

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Entity\ProductCategory;
use Symfony\Component\Uid\Uuid;

interface ProductCategoryRepositoryInterface
{
    public function save(ProductCategory $category, bool $flush = true): void;

    public function remove(ProductCategory $category): void;

    public function findById(Uuid $id): ?ProductCategory;

    /** @return list<ProductCategory> */
    public function findByShop(Uuid $shopId): array;
}
