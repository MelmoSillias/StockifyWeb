<?php

namespace App\Inventory\Application\Query\ListStockMovements;

final readonly class ListStockMovementsQuery
{
    public function __construct(
        public ?string $variantId = null,
    ) {
    }
}
