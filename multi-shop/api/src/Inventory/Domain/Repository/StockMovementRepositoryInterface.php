<?php

namespace App\Inventory\Domain\Repository;

use App\Inventory\Domain\Entity\StockMovement;
use Symfony\Component\Uid\Uuid;

interface StockMovementRepositoryInterface
{
    /** @return list<StockMovement> */
    public function findAll(?Uuid $variantId = null): array;

    public function save(StockMovement $movement, bool $flush = true): void;
}
