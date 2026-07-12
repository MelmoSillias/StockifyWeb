<?php

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Entity\Product;
use Symfony\Component\Uid\Uuid;

interface ProductRepositoryInterface
{
    public function save(Product $product, bool $flush = true): void;

    public function remove(Product $product): void;

    public function findById(Uuid $id): ?Product;

    /** @return list<Product> */
    public function findByShop(Uuid $shopId): array;
}
