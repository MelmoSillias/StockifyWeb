<?php

namespace App\Analytics\Application\Query\GetAnalyticsFinance;

use App\Analytics\Infrastructure\Persistence\Doctrine\AnalyticsReadRepository;

final class GetAnalyticsFinanceHandler
{
    public function __construct(
        private readonly AnalyticsReadRepository $repository,
    ) {
    }

    /** @return array<string, mixed> */
    public function handle(GetAnalyticsFinanceQuery $query): array
    {
        $cashFlow = $this->repository->getCashFlowSummary($query->from, $query->to);
        $accounts = $this->repository->getAccountBalances();
        $treasuryTotal = '0.00';
        foreach ($accounts as $account) {
            $treasuryTotal = bcadd($treasuryTotal, $account['balance'], 2);
        }

        $creances = $this->repository->getCreancesAging();
        $dettes = $this->repository->getDettesAging();
        $netPosition = bcsub(
            bcadd($treasuryTotal, $creances['open_balance'], 2),
            $dettes['open_balance'],
            2,
        );

        return [
            'cash_flow' => $cashFlow,
            'cash_flow_trend' => $this->repository->getCashFlowTrend($query->from, $query->to),
            'accounts' => $accounts,
            'treasury_total' => $treasuryTotal,
            'creances_balance' => $creances['open_balance'],
            'dettes_balance' => $dettes['open_balance'],
            'net_position' => $netPosition,
            'transaction_source_split' => $this->repository->getTransactionSourceSplit($query->from, $query->to),
        ];
    }
}
