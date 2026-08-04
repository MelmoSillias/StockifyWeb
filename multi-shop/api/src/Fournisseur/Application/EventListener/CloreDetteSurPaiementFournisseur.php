<?php

namespace App\Fournisseur\Application\EventListener;

use App\Fournisseur\Application\Service\DetteBalanceCalculator;
use App\Fournisseur\Domain\Event\PaiementFournisseurEnregistre;
use App\Fournisseur\Domain\Repository\DetteFournisseurRepositoryInterface;
use App\Fournisseur\Domain\Repository\PaiementFournisseurRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: PaiementFournisseurEnregistre::class)]
final class CloreDetteSurPaiementFournisseur
{
    public function __construct(
        private readonly DetteFournisseurRepositoryInterface $detteRepository,
        private readonly PaiementFournisseurRepositoryInterface $paiementFournisseurRepository,
        private readonly DetteBalanceCalculator $balanceCalculator,
    ) {
    }

    public function __invoke(PaiementFournisseurEnregistre $event): void
    {
        $detteId = $event->detteFournisseurId();
        $dette = $this->detteRepository->findById($detteId);
        if (null === $dette || null !== $dette->getCreditClosedAt()) {
            return;
        }

        $balance = $this->balanceCalculator->computeBalance($detteId, $dette->getTotalAmount());
        if (bccomp($balance, '0', 2) > 0) {
            return;
        }

        $paiement = $this->paiementFournisseurRepository->findById($event->paiementFournisseurId());
        if (null === $paiement) {
            return;
        }

        $dette->closeCredit($paiement->getPaidAt());
        $this->detteRepository->save($dette);
    }
}
