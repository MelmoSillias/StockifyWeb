<?php

namespace App\Facturation\Application\Service;

use App\Client\Domain\Repository\ClientRepositoryInterface;
use App\Commerce\Domain\Repository\VenteRepositoryInterface;
use App\Facturation\Application\Dto\CreanceDetailDto;
use App\Facturation\Application\Dto\CreancePaiementDto;
use App\Facturation\Domain\Entity\Facture;
use App\Facturation\Domain\Enum\CreanceFilterStatus;
use App\Facturation\Domain\Repository\FactureRepositoryInterface;
use App\Paiement\Application\Service\PaiementSerializer;
use App\Paiement\Domain\Entity\Paiement;
use App\Paiement\Domain\Repository\PaiementRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class CreanceDetailMapper
{
    public function __construct(
        private readonly FactureRepositoryInterface $factureRepository,
        private readonly PaiementRepositoryInterface $paiementRepository,
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly VenteRepositoryInterface $venteRepository,
        private readonly FactureBalanceCalculator $balanceCalculator,
        private readonly PaiementSerializer $paiementSerializer,
    ) {
    }

    /**
     * @return list<CreanceDetailDto>
     */
    public function mapAll(?Uuid $clientId, CreanceFilterStatus $status): array
    {
        $factures = $this->factureRepository->findCreances($clientId, $status);
        $items = [];

        foreach ($factures as $facture) {
            $dto = $this->map($facture);
            if (CreanceFilterStatus::Open === $status && $dto->isCancelled) {
                continue;
            }
            $items[] = $dto;
        }

        return $items;
    }

    public function map(Facture $facture): CreanceDetailDto
    {
        $clientId = $facture->getClientId();
        $clientName = '—';
        if (null !== $clientId) {
            $client = $this->clientRepository->findById($clientId);
            if (null !== $client) {
                $clientName = $client->getName();
            }
        }

        $factureId = $facture->getId();
        $paidAmount = $this->balanceCalculator->computePaidAmount($factureId);
        $balance = $this->balanceCalculator->computeBalance($factureId, $facture->getTotalAmount());

        $paiements = array_map(
            fn (Paiement $paiement) => $this->mapPaiement($paiement),
            $this->paiementRepository->findByFactureId($factureId),
        );

        $isCancelled = $this->isSourceCancelled($facture);
        $statut = $this->resolveStatut($isCancelled, $balance, $facture->getCreditClosedAt());

        return new CreanceDetailDto(
            id: (string) $factureId,
            numero: $facture->getNumero(),
            clientId: null !== $clientId ? (string) $clientId : '',
            clientName: $clientName,
            venteId: null !== $facture->getVenteId() ? (string) $facture->getVenteId() : null,
            sourceReference: $facture->getSourceReference(),
            totalAmount: $facture->getTotalAmount(),
            paidAmount: $paidAmount,
            balance: $balance,
            isCreance: $facture->isCreance(),
            creditClosedAt: $facture->getCreditClosedAt()?->format(\DateTimeInterface::ATOM),
            issuedAt: $facture->getIssuedAt()->format(\DateTimeInterface::ATOM),
            statut: $statut,
            isCancelled: $isCancelled,
            paiements: $paiements,
        );
    }

    private function mapPaiement(Paiement $paiement): CreancePaiementDto
    {
        return new CreancePaiementDto(
            id: (string) $paiement->getId(),
            reference: $paiement->getReference(),
            amount: $paiement->getAmount(),
            method: $this->paiementSerializer->resolveMethodCode($paiement) ?? 'unknown',
            paidAt: $paiement->getPaidAt()->format(\DateTimeInterface::ATOM),
            isCancelled: $paiement->isCancelled(),
        );
    }

    private function isSourceCancelled(Facture $facture): bool
    {
        $venteId = $facture->getVenteId();
        if (null === $venteId) {
            return false;
        }

        $vente = $this->venteRepository->findById($venteId);

        return null !== $vente && $vente->isCancelled();
    }

    private function resolveStatut(bool $isCancelled, string $balance, ?\DateTimeImmutable $creditClosedAt): string
    {
        if ($isCancelled) {
            return 'annulee';
        }

        if (null !== $creditClosedAt || bccomp($balance, '0', 2) <= 0) {
            return 'soldee';
        }

        return 'en_cours';
    }
}
