<?php

namespace App\Dashboard\Application\Query\GetDashboardPendingSupplierOrders;

use App\Dashboard\Infrastructure\Persistence\Doctrine\DashboardReadRepository;
use App\Fournisseur\Domain\Repository\FournisseurRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class GetDashboardPendingSupplierOrdersHandler
{
    public function __construct(
        private readonly DashboardReadRepository $dashboardReadRepository,
        private readonly FournisseurRepositoryInterface $fournisseurRepository,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function handle(GetDashboardPendingSupplierOrdersQuery $query): array
    {
        $orders = $this->dashboardReadRepository->findPendingSupplierOrders($query->limit);

        return array_map(function (array $order): array {
            $fournisseurName = null;
            $fournisseurId = $order['fournisseur_id'] ?? null;
            if (null !== $fournisseurId && Uuid::isValid((string) $fournisseurId)) {
                $fournisseur = $this->fournisseurRepository->findById(Uuid::fromString((string) $fournisseurId));
                $fournisseurName = $fournisseur?->getName();
            }

            return [
                'id' => $order['id'],
                'reference' => $order['reference'],
                'fournisseur_id' => $fournisseurId,
                'fournisseur_name' => $fournisseurName ?? 'Fournisseur inconnu',
                'status' => $order['status'],
                'total_amount' => $order['total_amount'],
                'expected_at' => $order['expected_at'],
            ];
        }, $orders);
    }
}
