<?php

namespace App\Facturation\Application\EventListener;

use App\Facturation\Application\Service\FactureBalanceCalculator;
use App\Facturation\Domain\Repository\FactureRepositoryInterface;
use App\Paiement\Domain\Event\PaiementEnregistre;
use App\Paiement\Domain\Repository\PaiementRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: PaiementEnregistre::class)]
final class CloreCreanceSurPaiement
{
    public function __construct(
        private readonly FactureRepositoryInterface $factureRepository,
        private readonly PaiementRepositoryInterface $paiementRepository,
        private readonly FactureBalanceCalculator $balanceCalculator,
    ) {
    }

    public function __invoke(PaiementEnregistre $event): void
    {
        $factureId = $event->factureId();
        if (null === $factureId) {
            return;
        }

        $facture = $this->factureRepository->findById($factureId);
        if (null === $facture || !$facture->isCreance() || null !== $facture->getCreditClosedAt()) {
            return;
        }

        $balance = $this->balanceCalculator->computeBalance($factureId, $facture->getTotalAmount());
        if (bccomp($balance, '0', 2) > 0) {
            return;
        }

        $paiement = $this->paiementRepository->findById($event->paiementId());
        if (null === $paiement) {
            return;
        }

        $facture->closeCredit($paiement->getPaidAt());
        $this->factureRepository->save($facture);
    }
}
