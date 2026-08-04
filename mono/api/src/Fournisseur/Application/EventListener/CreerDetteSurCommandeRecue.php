<?php

namespace App\Fournisseur\Application\EventListener;

use App\Fournisseur\Application\Service\PaiementFournisseurService;
use App\Fournisseur\Domain\Entity\DetteFournisseur;
use App\Fournisseur\Domain\Event\CommandeFournisseurRecue;
use App\Fournisseur\Domain\Repository\CommandeFournisseurRepositoryInterface;
use App\Fournisseur\Domain\Repository\DetteFournisseurRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Uid\Uuid;

#[AsEventListener(event: CommandeFournisseurRecue::class, priority: 0)]
final class CreerDetteSurCommandeRecue
{
    public function __construct(
        private readonly CommandeFournisseurRepositoryInterface $commandeRepository,
        private readonly DetteFournisseurRepositoryInterface $detteRepository,
        private readonly PaiementFournisseurService $paiementFournisseurService,
    ) {
    }

    public function __invoke(CommandeFournisseurRecue $event): void
    {
        $commande = $this->commandeRepository->findById($event->commandeFournisseurId());
        if (null === $commande) {
            return;
        }

        $dette = new DetteFournisseur(
            $event->fournisseurId(),
            $event->totalAmount(),
            sprintf('Achat %s', $commande->getReference()),
            $event->commandeFournisseurId(),
        );

        $this->detteRepository->save($dette);

        if (bccomp($event->paidAmount(), '0', 2) <= 0) {
            return;
        }

        $payload = [
            'dette_fournisseur_id' => (string) $dette->getId(),
            'amount' => $event->paidAmount(),
            'paid_at' => $event->paidAt()?->format(\DateTimeInterface::ATOM),
        ];

        $payload['mode_de_paiement_id'] = (string) $event->modeDePaiementId();

        $this->paiementFournisseurService->enregistrer($payload);
    }
}
