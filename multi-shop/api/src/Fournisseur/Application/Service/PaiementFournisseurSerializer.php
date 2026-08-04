<?php

namespace App\Fournisseur\Application\Service;

use App\Finance\Domain\Repository\ModeDePaiementRepositoryInterface;
use App\Fournisseur\Domain\Entity\PaiementFournisseur;

final class PaiementFournisseurSerializer
{
    public function __construct(
        private readonly ModeDePaiementRepositoryInterface $modeDePaiementRepository,
    ) {
    }

    /** @return array<string, mixed> */
    public function serialize(PaiementFournisseur $paiement): array
    {
        $mode = $this->modeDePaiementRepository->findById($paiement->getModeDePaiementId());

        return [
            'id' => (string) $paiement->getId(),
            'reference' => $paiement->getReference(),
            'dette_fournisseur_id' => (string) $paiement->getDetteFournisseurId(),
            'amount' => $paiement->getAmount(),
            'mode_de_paiement_id' => (string) $paiement->getModeDePaiementId(),
            'method' => $mode?->getCode(),
            'method_label' => $mode?->getLabel(),
            'paid_at' => $paiement->getPaidAt()->format(\DateTimeInterface::ATOM),
            'cancelled_at' => $paiement->getCancelledAt()?->format(\DateTimeInterface::ATOM),
            'is_cancelled' => $paiement->isCancelled(),
        ];
    }
}
