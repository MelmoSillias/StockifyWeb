<?php

namespace App\Commerce\Application\Service;

use App\Commerce\Application\Dto\CommandeDetailDto;
use App\Commerce\Application\Dto\VenteFactureDto;
use App\Commerce\Application\Dto\VenteLineDto;
use App\Commerce\Application\Dto\VentePaiementDto;
use App\Commerce\Domain\Entity\Commande;
use App\Commerce\Domain\Entity\CommandeLine;
use App\Commerce\Domain\Enum\CommandeStatus;
use App\Commerce\Domain\Enum\PaymentStatus;
use App\Facturation\Domain\Entity\Facture;
use App\Facturation\Domain\Repository\FactureRepositoryInterface;
use App\Paiement\Application\Service\PaiementSerializer;
use App\Paiement\Domain\Entity\Paiement;
use App\Paiement\Domain\Repository\PaiementRepositoryInterface;

final class CommandeDetailMapper
{
    public function __construct(
        private readonly FactureRepositoryInterface $factureRepository,
        private readonly PaiementRepositoryInterface $paiementRepository,
        private readonly AcheteurPresenter $acheteurPresenter,
        private readonly PaiementSerializer $paiementSerializer,
    ) {
    }

    public function map(Commande $commande): CommandeDetailDto
    {
        $lines = array_map(
            fn (CommandeLine $line) => $this->mapLine($line),
            $commande->getLines()->toArray(),
        );

        $facture = $this->factureRepository->findByCommandeId($commande->getId());
        $factureDto = null !== $facture ? $this->mapFacture($facture) : null;
        $paiements = array_map(
            fn (Paiement $paiement) => $this->mapPaiement($paiement),
            $this->collectPaiements($commande, $facture),
        );

        $paidAmount = $this->computePaidAmount($paiements);
        $totalAmount = $commande->getTotalAmount();
        $balance = $this->computeBalance($totalAmount, $paidAmount);
        $paymentStatus = $this->resolvePaymentStatus($commande, $totalAmount, $paidAmount);

        return new CommandeDetailDto(
            id: (string) $commande->getId(),
            reference: $commande->getReference(),
            acheteur: $this->acheteurPresenter->present($commande->getAcheteur()),
            status: $commande->getStatus()->value,
            totalAmount: $totalAmount,
            depositReceived: $commande->getDepositReceived(),
            deliveryDate: $commande->getDeliveryDate()?->format('Y-m-d'),
            createdAt: $commande->getCreatedAt()->format(\DateTimeInterface::ATOM),
            confirmedAt: $commande->getConfirmedAt()?->format(\DateTimeInterface::ATOM),
            cancelledAt: $commande->getCancelledAt()?->format(\DateTimeInterface::ATOM),
            lines: $lines,
            facture: $factureDto,
            paiements: $paiements,
            paymentStatus: $paymentStatus->value,
            paidAmount: $paidAmount,
            balance: $balance,
        );
    }

    /** @return list<Paiement> */
    private function collectPaiements(Commande $commande, ?Facture $facture): array
    {
        $paiements = $this->paiementRepository->findByCommandeId($commande->getId());

        if (null !== $facture) {
            $paiements = [...$paiements, ...$this->paiementRepository->findByFactureId($facture->getId())];
        }

        usort(
            $paiements,
            static fn (Paiement $left, Paiement $right) => $right->getPaidAt() <=> $left->getPaidAt(),
        );

        return $paiements;
    }

    private function mapLine(CommandeLine $line): VenteLineDto
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

    private function resolvePaymentStatus(Commande $commande, string $totalAmount, string $paidAmount): PaymentStatus
    {
        if (CommandeStatus::Annulee === $commande->getStatus()) {
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
