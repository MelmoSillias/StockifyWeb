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

    /**
     * Bulk available stock keyed by variant UUID string.
     *
     * @return array<string, string>
     */
    public function sumAvailableStockByVariant(): array;

    /**
     * Lot counts keyed by variant UUID string.
     *
     * @return array<string, int>
     */
    public function countLotsByVariant(): array;

    public function save(StockLot $lot, bool $flush = true): void;
}
