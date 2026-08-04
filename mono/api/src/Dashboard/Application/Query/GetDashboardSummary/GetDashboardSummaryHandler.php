<?php

namespace App\Dashboard\Application\Query\GetDashboardSummary;

use App\Dashboard\Application\Dto\DashboardSummaryDto;
use App\Dashboard\Infrastructure\Persistence\Doctrine\DashboardReadRepository;
use App\Inventory\Application\Query\GetStockAlerts\GetStockAlertsHandler;
use App\Inventory\Application\Query\GetStockAlerts\GetStockAlertsQuery;

final class GetDashboardSummaryHandler
{
    public function __construct(
        private readonly DashboardReadRepository $dashboardReadRepository,
        private readonly GetStockAlertsHandler $stockAlertsHandler,
    ) {
    }

    public function handle(GetDashboardSummaryQuery $query): DashboardSummaryDto
    {
        $sales = $this->dashboardReadRepository->getSalesSummary($query->from, $query->to);
        $deliveries = $this->dashboardReadRepository->getDeliveryCounts(new \DateTimeImmutable('today'));
        $lowStockCount = \count($this->stockAlertsHandler->handle(new GetStockAlertsQuery()));
        $activeClients = $this->dashboardReadRepository->countActiveClients($query->from, $query->to);

        return new DashboardSummaryDto(
            salesCount: $sales['count'],
            salesTotalAmount: $sales['total_amount'],
            lowStockCount: $lowStockCount,
            pendingDeliveryCount: $deliveries['pending_count'],
            overdueDeliveryCount: $deliveries['overdue_count'],
            activeClientsCount: $activeClients,
        );
    }
}
