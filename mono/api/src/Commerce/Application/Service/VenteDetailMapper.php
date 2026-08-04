<?php

namespace App\Commerce\Application\Service;

use App\Commerce\Application\Dto\VenteAvoirDto;
use App\Commerce\Application\Dto\VenteDetailDto;
use App\Commerce\Application\Dto\VenteFactureDto;
use App\Commerce\Application\Dto\VenteLineDto;
use App\Commerce\Application\Dto\VentePaiementDto;
use App\Commerce\Domain\Entity\Vente;
use App\Commerce\Domain\Entity\VenteLine;
use App\Commerce\Domain\Enum\PaymentStatus;
use App\Facturation\Domain\Entity\Avoir;
use App\Facturation\Domain\Entity\Facture;
use App\Facturation\Domain\Repository\AvoirRepositoryInterface;
use App\Facturation\Domain\Repository\FactureRepositoryInterface;
use App\Paiement\Application\Service\PaiementSerializer;
use App\Paiement\Domain\Entity\Paiement;
use App\Paiement\Domain\Repository\PaiementRepositoryInterface;

final class VenteDetailMapper
{
    public function __construct(
        private readonly FactureRepositoryInterface $factureRepository,
        private readonly PaiementRepositoryInterface $paiementRepository,
        private readonly AvoirRepositoryInterface $avoirRepository,
        private readonly AcheteurPresenter $acheteurPresenter,
        private readonly PaiementSerializer $paiementSerializer,
    ) {
    }

    public function map(Vente $vente): VenteDetailDto
    {
        $lines = array_map(
            fn (VenteLine $line) => $this->mapLine($line),
            $vente->getLines()->toArray(),
        );

        $facture = $this->factureRepository->findByVenteId($vente->getId());
        $factureDto = null !== $facture ? $this->mapFacture($facture) : null;

        $paiements = [];
        if (null !== $facture) {
            $paiements = array_map(
                fn (Paiement $paiement) => $this->mapPaiement($paiement),
                $this->paiementRepository->findByFactureId($facture->getId()),
            );
        }

        $avoir = $this->avoirRepository->findByVenteId($vente->getId());
        $avoirDto = null !== $avoir ? $this->mapAvoir($avoir) : null;

        $paidAmount = $this->computePaidAmount($paiements);
        $totalAmount = $vente->getTotalAmount();
        $balance = $this->computeBalance($totalAmount, $paidAmount);
        $paymentStatus = $this->resolvePaymentStatus($vente, $totalAmount, $paidAmount);

        return new VenteDetailDto(
            id: (string) $vente->getId(),
            reference: $vente->getReference(),
            acheteur: $this->acheteurPresenter->present($vente->getAcheteur()),
            totalAmount: $totalAmount,
            createdAt: $vente->getCreatedAt()->format(\DateTimeInterface::ATOM),
            cancelledAt: $vente->getCancelledAt()?->format(\DateTimeInterface::ATOM),
            lines: $lines,
            facture: $factureDto,
            avoir: $avoirDto,
            paiements: $paiements,
            paymentStatus: $paymentStatus->value,
            paidAmount: $paidAmount,
            balance: $balance,
        );
    }

    private function mapLine(VenteLine $line): VenteLineDto
    {
        return new VenteLineDto(
            id: (string) $line->getId(),
            variantId: (string) $line->getVariantId(),
            label: $line->getLabel(),
            quantity: $line->getQuantity(),
            unitPrice: $line->getUnitPrice(),
            lineTotal: $line->getLineTotal(),
        );
    }

    private function mapFacture(Facture $facture): VenteFactureDto
    {
        return new VenteFactureDto(
            id: (string) $facture->getId(),
            numero: $facture->getNumero(),
            totalAmount: $facture->getTotalAmount(),
            issuedAt: $facture->getIssuedAt()->format(\DateTimeInterface::ATOM),
            isCreance: $facture->isCreance(),
            creditClosedAt: $facture->getCreditClosedAt()?->format(\DateTimeInterface::ATOM),
        );
    }

    private function mapAvoir(Avoir $avoir): VenteAvoirDto
    {
        return new VenteAvoirDto(
            id: (string) $avoir->getId(),
            numero: $avoir->getNumero(),
            totalAmount: $avoir->getTotalAmount(),
            issuedAt: $avoir->getIssuedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    private function mapPaiement(Paiement $paiement): VentePaiementDto
    {
        return new VentePaiementDto(
            id: (string) $paiement->getId(),
            reference: $paiement->getReference(),
            amount: $paiement->getAmount(),
            method: $this->paiementSerializer->resolveMethodCode($paiement) ?? 'unknown',
            paidAt: $paiement->getPaidAt()->format(\DateTimeInterface::ATOM),
            isCancelled: $paiement->isCancelled(),
        );
    }

    /**
     * @param list<VentePaiementDto> $paiements
     */
    private function computePaidAmount(array $paiements): string
    {
        $sum = '0.00';
        foreach ($paiements as $paiement) {
            if (!$paiement->isCancelled) {
                $sum = bcadd($sum, $paiement->amount, 2);
            }
        }

        return $sum;
    }

    private function computeBalance(string $totalAmount, string $paidAmount): string
    {
        $balance = bcsub($totalAmount, $paidAmount, 2);

        return bccomp($balance, '0', 2) < 0 ? '0.00' : $balance;
    }

    private function resolvePaymentStatus(Vente $vente, string $totalAmount, string $paidAmount): PaymentStatus
    {
        if ($vente->isCancelled()) {
            return PaymentStatus::Annulee;
        }

        if (bccomp($paidAmount, '0', 2) <= 0) {
            return PaymentStatus::Impaye;
        }

        if (bccomp($paidAmount, $totalAmount, 2) >= 0) {
            return PaymentStatus::Paye;
        }

        return PaymentStatus::PartiellementPaye;
    }
}
