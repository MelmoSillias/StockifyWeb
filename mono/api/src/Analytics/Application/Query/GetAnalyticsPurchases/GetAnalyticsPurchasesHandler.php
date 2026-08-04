<?php

namespace App\Analytics\Application\Query\GetAnalyticsPurchases;

use App\Analytics\Infrastructure\Persistence\Doctrine\AnalyticsReadRepository;

final class GetAnalyticsPurchasesHandler
{
    public function __construct(
        private readonly AnalyticsReadRepository $repository,
    ) {
    }

    /** @return array<string, mixed> */
    public function handle(GetAnalyticsPurchasesQuery $query): array
    {
        return [
            'summary' => $this->repository->getPurchasesSummary($query->from, $query->to),
            'trend' => $this->repository->getPurchasesTrend($query->from, $query->to),
            'by_supplier' => $this->repository->getPurchasesBySupplier($query->from, $query->to, 10),
            'average_lead_time_days' => $this->repository->getAverageLeadTimeDays($query->from, $query->to),
            'dettes' => $this->repository->getDettesAging(),
        ];
    }
}
