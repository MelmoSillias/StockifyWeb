<?php

namespace App\Finance\Application\EventListener;

use App\Finance\Application\Service\TransactionService;
use App\Paiement\Domain\Event\PaiementAnnule;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: PaiementAnnule::class)]
final class AnnulerTransactionSurPaiementAnnule
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {
    }

    public function __invoke(PaiementAnnule $event): void
    {
        $this->transactionService->cancelByPaiementId($event->paiementId());
    }
}
