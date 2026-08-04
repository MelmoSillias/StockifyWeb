<?php

namespace App\Analytics\Application\Query\GetAnalyticsSales;

use App\Analytics\Infrastructure\Persistence\Doctrine\AnalyticsReadRepository;

final class GetAnalyticsSalesHandler
{
    public function __construct(
        private readonly AnalyticsReadRepository $repository,
    ) {
    }

    /** @return array<string, mixed> */
    public function handle(GetAnalyticsSalesQuery $query): array
    {
        $summary = $this->repository->getSalesSummary($query->from, $query->to);
        $avgTicket = $summary['count'] > 0
            ? bcdiv($summary['net_amount'], (string) $summary['count'], 2)
            : '0.00';

        return [
            'summary' => [
                ...$summary,
                'average_ticket' => $avgTicket,
            ],
            'trend' => $this->repository->getSalesTrend($query->from, $query->to),
            'top_products' => $this->repository->findTopProducts($query->from, $query->to, 10),
            'by_category' => $this->repository->getSalesByCategory($query->from, $query->to),
            'client_split' => $this->repository->getSalesClientSplit($query->from, $query->to),
            'order_pipeline' => $this->repository->getOrderPipeline($query->from, $query->to),
        ];
    }
}
