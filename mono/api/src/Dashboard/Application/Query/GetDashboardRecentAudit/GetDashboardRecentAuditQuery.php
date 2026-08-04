<?php

namespace App\Dashboard\Application\Query\GetDashboardRecentAudit;

final readonly class GetDashboardRecentAuditQuery
{
    public function __construct(
        public int $limit = 5,
    ) {
    }
}
