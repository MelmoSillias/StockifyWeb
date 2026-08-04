<?php

namespace App\Dashboard\Application\Query\GetDashboardFeed;

use App\Client\Domain\Repository\ClientRepositoryInterface;
use App\Dashboard\Application\Dto\DashboardFeedDto;
use App\Dashboard\Infrastructure\Persistence\Doctrine\DashboardReadRepository;
use App\Inventory\Application\Query\GetStockAlerts\GetStockAlertsHandler;
use App\Inventory\Application\Query\GetStockAlerts\GetStockAlertsQuery;
use Symfony\Component\Uid\Uuid;

final class GetDashboardFeedHandler
{
    public function __construct(
        private readonly DashboardReadRepository $dashboardReadRepository,
        private readonly GetStockAlertsHandler $stockAlertsHandler,
        private readonly ClientRepositoryInterface $clientRepository,
    ) {
    }

    public function handle(GetDashboardFeedQuery $query): DashboardFeedDto
    {
        $recentOrders = $this->enrichWithClientNames(
            $this->dashboardReadRepository->findRecentOrders($query->from, $query->to, $query->limit),
        );
        $recentSales = $this->enrichWithClientNames(
            $this->dashboardReadRepository->findRecentSales($query->from, $query->to, $query->limit),
        );
        $topProducts = $this->dashboardReadRepository->findTopProducts($query->from, $query->to, $query->limit);
        $recentMovements = $this->dashboardReadRepository->findRecentMovements($query->from, $query->to, $query->limit);
        $stockAlerts = \array_slice(
            $this->stockAlertsHandler->handle(new GetStockAlertsQuery()),
            0,
            $query->limit,
        );

        return new DashboardFeedDto(
            recentOrders: $recentOrders,
            recentSales: $recentSales,
            topProducts: $topProducts,
            recentMovements: $recentMovements,
            stockAlerts: $stockAlerts,
        );
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return list<array<string, mixed>>
     */
    private function enrichWithClientNames(array $items): array
    {
        return array_map(function (array $item): array {
            $item['client_name'] = null;
            $clientId = $item['client_id'] ?? null;
            if (null === $clientId || '' === $clientId || !Uuid::isValid((string) $clientId)) {
                return $item;
            }

            $client = $this->clientRepository->findById(Uuid::fromString((string) $clientId));
            if (null !== $client) {
                $item['client_name'] = $client->getName();
            }

            return $item;
        }, $items);
    }
}
