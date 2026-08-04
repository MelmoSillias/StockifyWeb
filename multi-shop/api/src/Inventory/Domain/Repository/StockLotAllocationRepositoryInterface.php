<?php

namespace App\Inventory\Domain\Repository;

use App\Inventory\Domain\Entity\StockLotAllocation;

interface StockLotAllocationRepositoryInterface
{
    public function save(StockLotAllocation $allocation, bool $flush = true): void;
}
