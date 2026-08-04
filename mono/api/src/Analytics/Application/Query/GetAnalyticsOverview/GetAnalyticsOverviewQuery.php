<?php

namespace App\Analytics\Application\Query\GetAnalyticsOverview;

final class GetAnalyticsOverviewQuery
{
    public function __construct(
        public readonly \DateTimeImmutable $from,
        public readonly \DateTimeImmutable $to,
        public readonly bool $compare = true,
    ) {
    }
}
