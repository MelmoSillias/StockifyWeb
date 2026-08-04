<?php

namespace App\Analytics\Application\Query\GetAnalyticsPayments;

final class GetAnalyticsPaymentsQuery
{
    public function __construct(
        public readonly \DateTimeImmutable $from,
        public readonly \DateTimeImmutable $to,
    ) {
    }
}
