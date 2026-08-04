<?php

namespace App\Dashboard\Application\Query\GetDashboardPendingSupplierOrders;

final readonly class GetDashboardPendingSupplierOrdersQuery
{
    public function __construct(
        public int $limit = 10,
    ) {
    }
}
