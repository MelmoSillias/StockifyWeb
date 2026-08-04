<?php

namespace App\Finance\Application\EventListener;

use App\Finance\Application\Service\TransactionService;
use App\Finance\Domain\Repository\ModeDePaiementRepositoryInterface;
use App\Paiement\Domain\Event\PaiementAnnule;
use App\Paiement\Domain\Event\PaiementEnregistre;
use App\Paiement\Domain\Repository\PaiementRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: PaiementEnregistre::class)]
final class CreerTransactionSurPaiementEnregistre
{
    public function __construct(
        private readonly PaiementRepositoryInterface $paiementRepository,
        private readonly ModeDePaiementRepositoryInterface $modeDePaiementRepository,
        private readonly TransactionService $transactionService,
    ) {
    }

    public function __invoke(PaiementEnregistre $event): void
    {
        $mode = $this->modeDePaiementRepository->findById($event->modeDePaiementId());
        if (null === $mode || !$mode->generatesTransaction()) {
            return;
        }

        $paiement = $this->paiementRepository->findById($event->paiementId());
        if (null === $paiement) {
            return;
        }

        $this->transactionService->createFromPaiement(
            $event->paiementId(),
            $mode->getCompteId(),
            $event->amount(),
            sprintf('Encaissement %s', $paiement->getReference()),
            $paiement->getPaidAt(),
        );
    }
}
