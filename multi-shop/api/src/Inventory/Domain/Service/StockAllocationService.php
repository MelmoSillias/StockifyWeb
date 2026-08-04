<?php

namespace App\Inventory\Domain\Service;

use App\Inventory\Domain\Entity\StockLot;
use App\Inventory\Domain\Entity\StockLotAllocation;
use App\Inventory\Domain\Entity\StockMovement;
use App\Inventory\Domain\Exception\InsufficientStockException;

final class StockAllocationService
{
    /**
     * @param list<StockLot> $lots
     * @return list<StockLotAllocation>
     */
    public function allocateFromLots(array $lots, string $quantity, StockMovement $movement): array
    {
        $remaining = $quantity;
        $allocations = [];

        foreach ($lots as $lot) {
            if (bccomp($remaining, '0', 3) <= 0) {
                break;
            }

            $take = bccomp($lot->getQuantityRemaining(), $remaining, 3) >= 0
                ? $remaining
                : $lot->getQuantityRemaining();

            $allocations[] = new StockLotAllocation($movement, $lot, $take, $lot->getUnitCost());
            $lot->consume($take);
            $remaining = bcsub($remaining, $take, 3);
        }

        if (bccomp($remaining, '0', 3) > 0) {
            throw new InsufficientStockException('Could not allocate full quantity from lots.');
        }

        return $allocations;
    }
}
