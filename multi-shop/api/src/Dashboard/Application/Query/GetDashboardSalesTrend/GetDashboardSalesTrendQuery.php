<?php

namespace App\Dashboard\Application\Query\GetDashboardSalesTrend;

final readonly class GetDashboardSalesTrendQuery
{
    public function __construct(
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $to,
    ) {
    }
}
