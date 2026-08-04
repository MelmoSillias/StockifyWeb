<?php

namespace App\Analytics\Application\Query\GetAnalyticsClients;

final class GetAnalyticsClientsQuery
{
    public function __construct(
        public readonly \DateTimeImmutable $from,
        public readonly \DateTimeImmutable $to,
    ) {
    }
}
