<?php

namespace App\Paiement\Application\Service;

use App\Finance\Domain\Entity\ModeDePaiement;
use App\Finance\Domain\Repository\ModeDePaiementRepositoryInterface;
use App\Paiement\Domain\Entity\Paiement;

final class PaiementSerializer
{
    public function __construct(
        private readonly ModeDePaiementRepositoryInterface $modeDePaiementRepository,
    ) {
    }

    /** @return array<string, mixed> */
    public function serialize(Paiement $paiement): array
    {
        $mode = $this->modeDePaiementRepository->findById($paiement->getModeDePaiementId());

        return [
            'id' => (string) $paiement->getId(),
            'reference' => $paiement->getReference(),
            'facture_id' => $paiement->getFactureId() ? (string) $paiement->getFactureId() : null,
            'commande_id' => $paiement->getCommandeId() ? (string) $paiement->getCommandeId() : null,
            'amount' => $paiement->getAmount(),
            'mode_de_paiement_id' => (string) $paiement->getModeDePaiementId(),
            'method' => $mode?->getCode(),
            'method_label' => $mode?->getLabel(),
            'paid_at' => $paiement->getPaidAt()->format(\DateTimeInterface::ATOM),
            'cancelled_at' => $paiement->getCancelledAt()?->format(\DateTimeInterface::ATOM),
            'is_cancelled' => $paiement->isCancelled(),
        ];
    }

    public function resolveMethodCode(Paiement $paiement): ?string
    {
        return $this->modeDePaiementRepository->findById($paiement->getModeDePaiementId())?->getCode();
    }

    public function resolveMethodLabel(Paiement $paiement): ?string
    {
        return $this->modeDePaiementRepository->findById($paiement->getModeDePaiementId())?->getLabel();
    }

    public function resolveMethod(?ModeDePaiement $mode): ?string
    {
        return $mode?->getCode();
    }
}
