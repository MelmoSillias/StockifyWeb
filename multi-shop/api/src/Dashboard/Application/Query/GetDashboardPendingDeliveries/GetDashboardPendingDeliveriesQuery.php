<?php

namespace App\Dashboard\Application\Query\GetDashboardPendingDeliveries;

final readonly class GetDashboardPendingDeliveriesQuery
{
    public function __construct(
        public int $limit = 10,
    ) {
    }
}
