<?php

namespace App\Inventory\Domain\Repository;

use App\Catalog\Domain\Entity\ProductVariant;
use App\Inventory\Domain\Entity\StockLot;
use App\Inventory\Domain\Enum\StockPolicyStrategy;
use Symfony\Component\Uid\Uuid;

interface StockLotRepositoryInterface
{
    /** @return list<StockLot> */
    public function findAvailableByVariant(ProductVariant $variant, StockPolicyStrategy $strategy): array;

    /** @return list<StockLot> */
    public function findByVariantOrderedByReceivedAt(ProductVariant $variant): array;

    public function findById(Uuid $id): ?StockLot;

    public function sumAvailableStock(ProductVariant $variant): string;

    public function save(StockLot $lot, bool $flush = true): void;
}
