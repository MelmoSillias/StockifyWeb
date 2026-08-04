<?php

namespace App\Dashboard\Application\Query\GetDashboardPendingDeliveries;

use App\Client\Domain\Repository\ClientRepositoryInterface;
use App\Commerce\Domain\Entity\Commande;
use App\Dashboard\Application\Dto\PendingDeliveryDto;
use App\Dashboard\Infrastructure\Persistence\Doctrine\DashboardReadRepository;
use Symfony\Component\Uid\Uuid;

final class GetDashboardPendingDeliveriesHandler
{
    public function __construct(
        private readonly DashboardReadRepository $dashboardReadRepository,
        private readonly ClientRepositoryInterface $clientRepository,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function handle(GetDashboardPendingDeliveriesQuery $query): array
    {
        $today = new \DateTimeImmutable('today');
        $commandes = $this->dashboardReadRepository->findPendingDeliveries($query->limit, $today);

        return array_map(
            fn (Commande $commande): array => $this->mapCommande($commande, $today)->toArray(),
            $commandes,
        );
    }

    private function mapCommande(Commande $commande, \DateTimeImmutable $today): PendingDeliveryDto
    {
        $acheteur = $commande->getAcheteur();
        $clientName = null;
        $clientId = $acheteur->clientId();
        if (null !== $clientId) {
            $client = $this->clientRepository->findById($clientId);
            $clientName = $client?->getName();
        }

        $deliveryDate = $commande->getDeliveryDate();
        $deliveryDateStr = $deliveryDate?->format('Y-m-d') ?? '';
        $isOverdue = null !== $deliveryDate && $deliveryDate < $today;

        return new PendingDeliveryDto(
            id: (string) $commande->getId(),
            reference: $commande->getReference(),
            clientName: $clientName,
            anonymousInfo: $acheteur->anonymousInfo(),
            status: $commande->getStatus()->value,
            deliveryDate: $deliveryDateStr,
            totalAmount: $commande->getTotalAmount(),
            isOverdue: $isOverdue,
        );
    }
}
