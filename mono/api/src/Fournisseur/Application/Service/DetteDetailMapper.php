<?php

namespace App\Fournisseur\Application\Service;

use App\Finance\Domain\Repository\ModeDePaiementRepositoryInterface;
use App\Fournisseur\Application\Dto\DetteDetailDto;
use App\Fournisseur\Application\Dto\DettePaiementDto;
use App\Fournisseur\Domain\Entity\DetteFournisseur;
use App\Fournisseur\Domain\Entity\PaiementFournisseur;
use App\Fournisseur\Domain\Enum\DetteFilterStatus;
use App\Fournisseur\Domain\Repository\CommandeFournisseurRepositoryInterface;
use App\Fournisseur\Domain\Repository\DetteFournisseurRepositoryInterface;
use App\Fournisseur\Domain\Repository\FournisseurRepositoryInterface;
use App\Fournisseur\Domain\Repository\PaiementFournisseurRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class DetteDetailMapper
{
    public function __construct(
        private readonly DetteFournisseurRepositoryInterface $detteRepository,
        private readonly PaiementFournisseurRepositoryInterface $paiementFournisseurRepository,
        private readonly FournisseurRepositoryInterface $fournisseurRepository,
        private readonly CommandeFournisseurRepositoryInterface $commandeFournisseurRepository,
        private readonly DetteBalanceCalculator $balanceCalculator,
        private readonly ModeDePaiementRepositoryInterface $modeDePaiementRepository,
    ) {
    }

    /**
     * @return list<DetteDetailDto>
     */
    public function mapAll(?Uuid $fournisseurId, DetteFilterStatus $status): array
    {
        $dettes = $this->detteRepository->findDettes($fournisseurId, $status);

        return array_map(fn (DetteFournisseur $dette) => $this->map($dette), $dettes);
    }

    public function map(DetteFournisseur $dette): DetteDetailDto
    {
        $fournisseurId = $dette->getFournisseurId();
        $fournisseur = $this->fournisseurRepository->findById($fournisseurId);
        $fournisseurName = $fournisseur?->getName() ?? '—';

        $detteId = $dette->getId();
        $paidAmount = $this->balanceCalculator->computePaidAmount($detteId);
        $balance = $this->balanceCalculator->computeBalance($detteId, $dette->getTotalAmount());

        $commandeReference = null;
        $commandeId = $dette->getCommandeFournisseurId();
        if (null !== $commandeId) {
            $commande = $this->commandeFournisseurRepository->findById($commandeId);
            $commandeReference = $commande?->getReference();
        }

        $paiements = array_map(
            fn (PaiementFournisseur $paiement) => $this->mapPaiement($paiement),
            $this->paiementFournisseurRepository->findByDetteId($detteId),
        );

        $statut = $this->resolveStatut($balance, $dette->getCreditClosedAt());

        return new DetteDetailDto(
            id: (string) $detteId,
            reference: $dette->getReference(),
            fournisseurId: (string) $fournisseurId,
            fournisseurName: $fournisseurName,
            commandeFournisseurId: null !== $commandeId ? (string) $commandeId : null,
            commandeReference: $commandeReference,
            totalAmount: $dette->getTotalAmount(),
            paidAmount: $paidAmount,
            balance: $balance,
            label: $dette->getLabel(),
            creditClosedAt: $dette->getCreditClosedAt()?->format(\DateTimeInterface::ATOM),
            issuedAt: $dette->getIssuedAt()->format(\DateTimeInterface::ATOM),
            statut: $statut,
            paiements: $paiements,
        );
    }

    private function mapPaiement(PaiementFournisseur $paiement): DettePaiementDto
    {
        $mode = $this->modeDePaiementRepository->findById($paiement->getModeDePaiementId());

        return new DettePaiementDto(
            id: (string) $paiement->getId(),
            reference: $paiement->getReference(),
            amount: $paiement->getAmount(),
            method: $mode?->getCode() ?? 'unknown',
            paidAt: $paiement->getPaidAt()->format(\DateTimeInterface::ATOM),
            isCancelled: $paiement->isCancelled(),
        );
    }

    private function resolveStatut(string $balance, ?\DateTimeImmutable $creditClosedAt): string
    {
        if (null !== $creditClosedAt || bccomp($balance, '0', 2) <= 0) {
            return 'soldee';
        }

        return 'en_cours';
    }
}
