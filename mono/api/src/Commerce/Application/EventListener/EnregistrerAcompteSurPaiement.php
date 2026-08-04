<?php

namespace App\Commerce\Application\EventListener;

use App\Commerce\Domain\Repository\CommandeRepositoryInterface;
use App\Paiement\Domain\Event\PaiementEnregistre;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * When a payment targets an order (deposit / acompte), keep the order's
 * running deposit total in sync without ever mutating any invoice.
 */
#[AsEventListener(event: PaiementEnregistre::class)]
final class EnregistrerAcompteSurPaiement
{
    public function __construct(
        private readonly CommandeRepositoryInterface $commandeRepository,
    ) {
    }

    public function __invoke(PaiementEnregistre $event): void
    {
        $commandeId = $event->commandeId();
        if (null === $commandeId) {
            return;
        }

        $commande = $this->commandeRepository->findById($commandeId);
        if (null === $commande) {
            return;
        }

        $commande->registerDeposit($event->amount());
        $this->commandeRepository->save($commande);
    }
}
