<?php

namespace App\Finance\Application\EventListener;

use App\Finance\Application\Service\TransactionService;
use App\Finance\Domain\Repository\ModeDePaiementRepositoryInterface;
use App\Fournisseur\Domain\Event\PaiementFournisseurEnregistre;
use App\Fournisseur\Domain\Repository\PaiementFournisseurRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: PaiementFournisseurEnregistre::class)]
final class CreerTransactionDepenseSurPaiementFournisseur
{
    public function __construct(
        private readonly PaiementFournisseurRepositoryInterface $paiementFournisseurRepository,
        private readonly ModeDePaiementRepositoryInterface $modeDePaiementRepository,
        private readonly TransactionService $transactionService,
    ) {
    }

    public function __invoke(PaiementFournisseurEnregistre $event): void
    {
        $mode = $this->modeDePaiementRepository->findById($event->modeDePaiementId());
        if (null === $mode || !$mode->generatesTransaction()) {
            return;
        }

        $paiement = $this->paiementFournisseurRepository->findById($event->paiementFournisseurId());
        if (null === $paiement) {
            return;
        }

        $this->transactionService->createFromPaiementFournisseur(
            $event->paiementFournisseurId(),
            $mode->getCompteId(),
            $event->amount(),
            sprintf('Décaissement %s', $paiement->getReference()),
            $paiement->getPaidAt(),
        );
    }
}
