<?php

namespace App\Inventory\Domain\Repository;

use App\Catalog\Domain\Entity\ProductVariant;
use App\Inventory\Domain\Entity\StockPolicy;

interface StockPolicyRepositoryInterface
{
    public function findByVariant(ProductVariant $variant): ?StockPolicy;

    public function save(StockPolicy $policy, bool $flush = true): void;
}
