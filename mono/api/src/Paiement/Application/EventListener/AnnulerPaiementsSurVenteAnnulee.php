<?php

namespace App\Paiement\Application\EventListener;

use App\Commerce\Domain\Event\VenteAnnulee;
use App\Paiement\Application\Service\PaiementService;
use App\Paiement\Domain\Repository\PaiementRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: VenteAnnulee::class)]
final class AnnulerPaiementsSurVenteAnnulee
{
    public function __construct(
        private readonly PaiementRepositoryInterface $paiementRepository,
        private readonly PaiementService $paiementService,
    ) {
    }

    public function __invoke(VenteAnnulee $event): void
    {
        foreach ($this->paiementRepository->findByFactureId($event->factureId()) as $paiement) {
            if ($paiement->isCancelled()) {
                continue;
            }

            $this->paiementService->annuler($paiement);
        }
    }
}
