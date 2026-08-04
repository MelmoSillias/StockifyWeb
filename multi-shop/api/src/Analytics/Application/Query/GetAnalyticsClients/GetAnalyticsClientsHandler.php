<?php

namespace App\Analytics\Application\Query\GetAnalyticsClients;

use App\Analytics\Infrastructure\Persistence\Doctrine\AnalyticsReadRepository;

final class GetAnalyticsClientsHandler
{
    public function __construct(
        private readonly AnalyticsReadRepository $repository,
    ) {
    }

    /** @return array<string, mixed> */
    public function handle(GetAnalyticsClientsQuery $query): array
    {
        $activeCount = $this->repository->countActiveClients($query->from, $query->to);
        $newCount = $this->repository->countNewClients($query->from, $query->to);
        $salesSummary = $this->repository->getSalesSummary($query->from, $query->to);

        $avgFrequency = $activeCount > 0
            ? bcdiv((string) $salesSummary['count'], (string) $activeCount, 2)
            : '0.00';

        return [
            'active_count' => $activeCount,
            'new_count' => $newCount,
            'average_purchase_frequency' => $avgFrequency,
            'top_clients' => $this->repository->getTopClientsByRevenue($query->from, $query->to, 10),
            'top_debtors' => $this->repository->getTopCreancesDebtors(10),
        ];
    }
}
