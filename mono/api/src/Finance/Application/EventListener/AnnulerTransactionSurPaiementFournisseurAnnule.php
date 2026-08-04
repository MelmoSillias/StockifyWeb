<?php

namespace App\Finance\Application\EventListener;

use App\Finance\Application\Service\TransactionService;
use App\Fournisseur\Domain\Event\PaiementFournisseurAnnule;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: PaiementFournisseurAnnule::class)]
final class AnnulerTransactionSurPaiementFournisseurAnnule
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {
    }

    public function __invoke(PaiementFournisseurAnnule $event): void
    {
        $this->transactionService->cancelByPaiementFournisseurId($event->paiementFournisseurId());
    }
}
