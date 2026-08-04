<?php

namespace App\Analytics\Application\Query\GetAnalyticsPurchases;

final class GetAnalyticsPurchasesQuery
{
    public function __construct(
        public readonly \DateTimeImmutable $from,
        public readonly \DateTimeImmutable $to,
    ) {
    }
}
