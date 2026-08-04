<?php

namespace App\Dashboard\Application\Query\GetDashboardSummary;

final readonly class GetDashboardSummaryQuery
{
    public function __construct(
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $to,
    ) {
    }
}
