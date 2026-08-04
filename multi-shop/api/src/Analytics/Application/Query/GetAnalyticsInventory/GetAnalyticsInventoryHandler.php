<?php

namespace App\Analytics\Application\Query\GetAnalyticsInventory;

use App\Analytics\Infrastructure\Persistence\Doctrine\AnalyticsReadRepository;

final class GetAnalyticsInventoryHandler
{
    public function __construct(
        private readonly AnalyticsReadRepository $repository,
    ) {
    }

    /** @return array<string, mixed> */
    public function handle(GetAnalyticsInventoryQuery $query): array
    {
        return [
            'stock_value' => $this->repository->getStockValuation(),
            'low_stock_count' => $this->repository->countLowStockAlerts(),
            'expiring_lots_count' => $this->repository->countExpiringLots(),
            'movements' => $this->repository->getMovementsSummary($query->from, $query->to),
            'top_margins' => $this->repository->getTopMargins(10),
        ];
    }
}
