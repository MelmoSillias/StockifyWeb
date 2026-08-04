<?php

namespace App\Dashboard\Application\Dto;

final readonly class DashboardFeedDto
{
    /**
     * @param list<array<string, mixed>> $recentOrders
     * @param list<array<string, mixed>> $recentSales
     * @param list<array<string, mixed>> $topProducts
     * @param list<array<string, mixed>> $recentMovements
     * @param list<array<string, mixed>> $stockAlerts
     */
    public function __construct(
        public array $recentOrders,
        public array $recentSales,
        public array $topProducts,
        public array $recentMovements,
        public array $stockAlerts,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'recent_orders' => $this->recentOrders,
            'recent_sales' => $this->recentSales,
            'top_products' => $this->topProducts,
            'recent_movements' => $this->recentMovements,
            'stock_alerts' => $this->stockAlerts,
        ];
    }
}
