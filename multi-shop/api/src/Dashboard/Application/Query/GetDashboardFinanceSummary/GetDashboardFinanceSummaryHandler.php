<?php

namespace App\Dashboard\Application\Query\GetDashboardFinanceSummary;

use App\AccessAudit\Application\Service\PermissionResolverService;
use App\Dashboard\Application\Dto\DashboardFinanceSummaryDto;
use App\Facturation\Application\Service\CreanceDetailMapper;
use App\Facturation\Domain\Enum\CreanceFilterStatus;
use App\Finance\Application\Service\CompteService;
use App\Finance\Domain\Repository\CompteRepositoryInterface;
use App\Fournisseur\Application\Service\DetteDetailMapper;
use App\Fournisseur\Domain\Enum\DetteFilterStatus;
use App\IdentityAccess\Domain\Entity\User;

final class GetDashboardFinanceSummaryHandler
{
    public function __construct(
        private readonly PermissionResolverService $permissionResolver,
        private readonly CreanceDetailMapper $creanceDetailMapper,
        private readonly DetteDetailMapper $detteDetailMapper,
        private readonly CompteRepositoryInterface $compteRepository,
        private readonly CompteService $compteService,
    ) {
    }

    public function handle(GetDashboardFinanceSummaryQuery $query, User $user): DashboardFinanceSummaryDto
    {
        $clientCreances = null;
        if ($this->permissionResolver->hasPermission($user, 'client.creances.view')) {
            $clientCreances = $this->buildBalanceSummary(
                $this->creanceDetailMapper->mapAll(null, CreanceFilterStatus::Open),
                static fn ($item) => $item->clientName,
            );
        }

        $supplierDettes = null;
        if ($this->permissionResolver->hasPermission($user, 'fournisseur.dettes.view')) {
            $supplierDettes = $this->buildBalanceSummary(
                $this->detteDetailMapper->mapAll(null, DetteFilterStatus::Open),
                static fn ($item) => $item->fournisseurName,
            );
        }

        $treasury = null;
        if ($this->permissionResolver->hasPermission($user, 'finance.comptes.view')) {
            $accounts = array_values(array_filter(
                $this->compteRepository->findAll(),
                static fn ($compte) => $compte->isActive(),
            ));

            $totalBalance = '0.00';
            $accountItems = [];

            foreach ($accounts as $compte) {
                $balance = $this->compteService->computeBalance($compte->getId());
                $totalBalance = bcadd($totalBalance, $balance, 2);
                $accountItems[] = [
                    'id' => (string) $compte->getId(),
                    'name' => $compte->getName(),
                    'balance' => $balance,
                ];
            }

            usort(
                $accountItems,
                static fn (array $left, array $right): int => bccomp($right['balance'], $left['balance'], 2),
            );

            $treasury = [
                'total_balance' => $totalBalance,
                'account_count' => \count($accountItems),
                'accounts' => $accountItems,
            ];
        }

        return new DashboardFinanceSummaryDto(
            clientCreances: $clientCreances,
            supplierDettes: $supplierDettes,
            treasury: $treasury,
        );
    }

    /**
     * @template T
     *
     * @param list<T> $items
     * @param callable(T): string $labelResolver
     *
     * @return array<string, mixed>
     */
    private function buildBalanceSummary(array $items, callable $labelResolver): array
    {
        $totalBalance = '0.00';
        $topItems = [];

        foreach ($items as $item) {
            if (method_exists($item, 'toArray')) {
                $data = $item->toArray();
            } else {
                $data = (array) $item;
            }

            if (($data['is_cancelled'] ?? false) === true) {
                continue;
            }

            $balance = (string) ($data['balance'] ?? '0.00');
            if (1 !== bccomp($balance, '0', 2)) {
                continue;
            }

            $totalBalance = bcadd($totalBalance, $balance, 2);
            $topItems[] = [
                'id' => (string) ($data['id'] ?? ''),
                'label' => $labelResolver($item),
                'balance' => $balance,
            ];
        }

        usort(
            $topItems,
            static fn (array $left, array $right): int => bccomp($right['balance'], $left['balance'], 2),
        );

        return [
            'total_balance' => $totalBalance,
            'count' => \count($topItems),
            'top_items' => \array_slice($topItems, 0, 5),
        ];
    }
}
