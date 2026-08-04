<?php

namespace App\Analytics\Application\Query\GetAnalyticsPayments;

use App\Analytics\Infrastructure\Persistence\Doctrine\AnalyticsReadRepository;

final class GetAnalyticsPaymentsHandler
{
    public function __construct(
        private readonly AnalyticsReadRepository $repository,
    ) {
    }

    /** @return array<string, mixed> */
    public function handle(GetAnalyticsPaymentsQuery $query): array
    {
        $creances = $this->repository->getCreancesAging();

        return [
            'summary' => $this->repository->getPaymentsSummary($query->from, $query->to),
            'trend' => $this->repository->getPaymentsTrend($query->from, $query->to),
            'by_mode' => $this->repository->getPaymentsByMode($query->from, $query->to),
            'creances' => $creances,
            'top_debtors' => $this->repository->getTopCreancesDebtors(10),
        ];
    }
}
