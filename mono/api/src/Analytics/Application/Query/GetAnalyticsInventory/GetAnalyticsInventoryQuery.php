<?php

namespace App\Analytics\Application\Query\GetAnalyticsInventory;

final class GetAnalyticsInventoryQuery
{
    public function __construct(
        public readonly \DateTimeImmutable $from,
        public readonly \DateTimeImmutable $to,
    ) {
    }
}
