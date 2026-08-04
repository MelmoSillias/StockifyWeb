<?php

namespace App\Analytics\Application\Query\GetAnalyticsSales;

final class GetAnalyticsSalesQuery
{
    public function __construct(
        public readonly \DateTimeImmutable $from,
        public readonly \DateTimeImmutable $to,
    ) {
    }
}
