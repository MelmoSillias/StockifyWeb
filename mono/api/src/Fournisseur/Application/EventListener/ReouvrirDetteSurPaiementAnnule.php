<?php

namespace App\Fournisseur\Application\EventListener;

use App\Fournisseur\Application\Service\DetteBalanceCalculator;
use App\Fournisseur\Domain\Event\PaiementFournisseurAnnule;
use App\Fournisseur\Domain\Repository\DetteFournisseurRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: PaiementFournisseurAnnule::class)]
final class ReouvrirDetteSurPaiementAnnule
{
    public function __construct(
        private readonly DetteFournisseurRepositoryInterface $detteRepository,
        private readonly DetteBalanceCalculator $balanceCalculator,
    ) {
    }

    public function __invoke(PaiementFournisseurAnnule $event): void
    {
        $detteId = $event->detteFournisseurId();
        $dette = $this->detteRepository->findById($detteId);
        if (null === $dette || null === $dette->getCreditClosedAt()) {
            return;
        }

        $balance = $this->balanceCalculator->computeBalance($detteId, $dette->getTotalAmount());
        if (bccomp($balance, '0', 2) <= 0) {
            return;
        }

        $dette->reopenCredit();
        $this->detteRepository->save($dette);
    }
}
