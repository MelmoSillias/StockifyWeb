<?php

namespace App\Dashboard\Application\Dto;

final readonly class DashboardSummaryDto
{
    public function __construct(
        public int $salesCount,
        public string $salesTotalAmount,
        public int $lowStockCount,
        public int $pendingDeliveryCount,
        public int $overdueDeliveryCount,
        public int $activeClientsCount,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'sales' => [
                'count' => $this->salesCount,
                'total_amount' => $this->salesTotalAmount,
            ],
            'stock' => [
                'low_stock_count' => $this->lowStockCount,
            ],
            'deliveries' => [
                'pending_count' => $this->pendingDeliveryCount,
                'overdue_count' => $this->overdueDeliveryCount,
            ],
            'clients' => [
                'active_count' => $this->activeClientsCount,
            ],
        ];
    }
}
