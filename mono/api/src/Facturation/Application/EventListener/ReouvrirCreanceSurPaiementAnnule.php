<?php

namespace App\Facturation\Application\EventListener;

use App\Facturation\Application\Service\FactureBalanceCalculator;
use App\Facturation\Domain\Repository\FactureRepositoryInterface;
use App\Paiement\Domain\Event\PaiementAnnule;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: PaiementAnnule::class)]
final class ReouvrirCreanceSurPaiementAnnule
{
    public function __construct(
        private readonly FactureRepositoryInterface $factureRepository,
        private readonly FactureBalanceCalculator $balanceCalculator,
    ) {
    }

    public function __invoke(PaiementAnnule $event): void
    {
        $factureId = $event->factureId();
        if (null === $factureId) {
            return;
        }

        $facture = $this->factureRepository->findById($factureId);
        if (null === $facture || !$facture->isCreance() || null === $facture->getCreditClosedAt()) {
            return;
        }

        $balance = $this->balanceCalculator->computeBalance($factureId, $facture->getTotalAmount());
        if (bccomp($balance, '0', 2) <= 0) {
            return;
        }

        $facture->reopenCredit();
        $this->factureRepository->save($facture);
    }
}
