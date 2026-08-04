<?php

namespace App\Analytics\Application\Query\GetAnalyticsOverview;

use App\Analytics\Application\Service\AnalyticsPeriodHelper;
use App\Analytics\Infrastructure\Persistence\Doctrine\AnalyticsReadRepository;

final class GetAnalyticsOverviewHandler
{
    public function __construct(
        private readonly AnalyticsReadRepository $repository,
    ) {
    }

    /** @return array<string, mixed> */
    public function handle(GetAnalyticsOverviewQuery $query): array
    {
        $sales = $this->repository->getSalesSummary($query->from, $query->to);
        $payments = $this->repository->getPaymentsSummary($query->from, $query->to);
        $purchases = $this->repository->getPurchasesSummary($query->from, $query->to);
        $cashFlow = $this->repository->getCashFlowSummary($query->from, $query->to);
        $activeClients = $this->repository->countActiveClients($query->from, $query->to);
        $newClients = $this->repository->countNewClients($query->from, $query->to);

        $projection = AnalyticsPeriodHelper::computeLinearProjection(
            $sales['net_amount'],
            $query->from,
            $query->to,
        );

        $comparison = null;
        if ($query->compare) {
            [$prevFrom, $prevTo] = AnalyticsPeriodHelper::previousPeriod($query->from, $query->to);
            $prevSales = $this->repository->getSalesSummary($prevFrom, $prevTo);
            $prevPayments = $this->repository->getPaymentsSummary($prevFrom, $prevTo);
            $prevPurchases = $this->repository->getPurchasesSummary($prevFrom, $prevTo);
            $prevCashFlow = $this->repository->getCashFlowSummary($prevFrom, $prevTo);
            $prevActiveClients = $this->repository->countActiveClients($prevFrom, $prevTo);

            $comparison = [
                'period' => [
                    'from' => $prevFrom->format('Y-m-d'),
                    'to' => $prevTo->format('Y-m-d'),
                ],
                'sales' => [
                    'total_amount_delta' => AnalyticsPeriodHelper::computeDeltaPercent($sales['net_amount'], $prevSales['net_amount']),
                    'count_delta' => AnalyticsPeriodHelper::computeDeltaPercentInt($sales['count'], $prevSales['count']),
                ],
                'payments' => [
                    'total_amount_delta' => AnalyticsPeriodHelper::computeDeltaPercent($payments['total_amount'], $prevPayments['total_amount']),
                ],
                'purchases' => [
                    'total_amount_delta' => AnalyticsPeriodHelper::computeDeltaPercent($purchases['total_amount'], $prevPurchases['total_amount']),
                ],
                'cash_flow' => [
                    'net_delta' => AnalyticsPeriodHelper::computeDeltaPercent($cashFlow['net'], $prevCashFlow['net']),
                ],
                'clients' => [
                    'active_count_delta' => AnalyticsPeriodHelper::computeDeltaPercentInt($activeClients, $prevActiveClients),
                ],
            ];
        }

        $avgTicket = $sales['count'] > 0
            ? bcdiv($sales['net_amount'], (string) $sales['count'], 2)
            : '0.00';

        return [
            'period' => [
                'from' => $query->from->format('Y-m-d'),
                'to' => $query->to->format('Y-m-d'),
            ],
            'comparison' => $comparison,
            'sales' => [
                'count' => $sales['count'],
                'total_amount' => $sales['total_amount'],
                'net_amount' => $sales['net_amount'],
                'avoir_amount' => $sales['avoir_amount'],
                'cancelled_count' => $sales['cancelled_count'],
                'average_ticket' => $avgTicket,
            ],
            'projection' => $projection,
            'payments' => $payments,
            'purchases' => $purchases,
            'cash_flow' => $cashFlow,
            'inventory' => [
                'stock_value' => $this->repository->getStockValuation(),
                'low_stock_count' => $this->repository->countLowStockAlerts(),
                'expiring_lots_count' => $this->repository->countExpiringLots(),
            ],
            'clients' => [
                'active_count' => $activeClients,
                'new_count' => $newClients,
            ],
        ];
    }
}
