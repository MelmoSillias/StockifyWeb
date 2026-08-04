<?php

namespace App\Analytics\Application\Query\GetAnalyticsFinance;

final class GetAnalyticsFinanceQuery
{
    public function __construct(
        public readonly \DateTimeImmutable $from,
        public readonly \DateTimeImmutable $to,
    ) {
    }
}
