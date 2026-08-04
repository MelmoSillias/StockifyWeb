<?php

namespace App\Dashboard\Application\Query\GetDashboardFeed;

final readonly class GetDashboardFeedQuery
{
    public function __construct(
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $to,
        public int $limit = 5,
    ) {
    }
}
