<?php

namespace App\Commerce\Application\Service;

use App\Catalog\Domain\Entity\ProductVariant;
use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use App\Commerce\Domain\Entity\Commande;
use App\Commerce\Domain\Entity\Vente;
use App\Commerce\Domain\Event\CommandeAnnulee;
use App\Commerce\Domain\Event\CommandeConfirmee;
use App\Commerce\Domain\Event\OperationLine;
use App\Commerce\Domain\Event\VenteAnnulee;
use App\Commerce\Domain\Event\VenteRealisee;
use App\Commerce\Domain\Repository\CommandeRepositoryInterface;
use App\Inventory\Application\Service\StockAvailabilityService;
use App\Commerce\Domain\Repository\VenteRepositoryInterface;
use App\Commerce\Domain\ValueObject\Acheteur;
use App\Facturation\Domain\Repository\FactureRepositoryInterface;
use App\Paiement\Application\Service\PaiementService;
use App\SharedKernel\Application\Event\DomainEventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

final class CommerceService
{
    public function __construct(
        private readonly VenteRepositoryInterface $venteRepository,
        private readonly CommandeRepositoryInterface $commandeRepository,
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly FactureRepositoryInterface $factureRepository,
        private readonly PaiementService $paiementService,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly StockAvailabilityService $stockAvailabilityService,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function realiserVente(array $payload): Vente
    {
        $acheteur = Acheteur::fromArray($payload['acheteur'] ?? []);
        $createdAt = $this->parseCreatedAt($payload['created_at'] ?? null);
        $vente = new Vente($acheteur, $createdAt);

        foreach ($this->normalizeLines($payload['lines'] ?? []) as $line) {
            $vente->addLine($line['variant_id'], $line['label'], $line['quantity'], $line['unit_price']);
        }

        if ($vente->getLines()->isEmpty()) {
            throw new \InvalidArgumentException('A sale must contain at least one line.');
        }

        $this->venteRepository->save($vente);

        $this->eventDispatcher->dispatch(new VenteRealisee(
            $vente->getId(),
            $vente->getReference(),
            $this->operationLines($vente->getLines()->toArray()),
            $vente->getTotalAmount(),
            $vente->getAcheteur()->toArray(),
        ));

        $facture = $this->factureRepository->findByVenteId($vente->getId());
        if (null === $facture) {
            throw new \RuntimeException('Invoice was not generated for the sale.');
        }

        $paidAmount = '0.00';
        $lastPaidAt = null;
        $initialPayment = $payload['initial_payment'] ?? null;
        if (\is_array($initialPayment) && !empty($initialPayment['amount'])) {
            $paiementPayload = [
                'facture_id' => (string) $facture->getId(),
                'amount' => (string) $initialPayment['amount'],
                'method' => (string) ($initialPayment['method'] ?? ''),
            ];
            if (!empty($initialPayment['paid_at'])) {
                $paiementPayload['paid_at'] = (string) $initialPayment['paid_at'];
            }

            $paiement = $this->paiementService->enregistrer($paiementPayload);
            $paidAmount = $paiement->getAmount();
            $lastPaidAt = $paiement->getPaidAt();
        }

        $isCreance = bccomp($paidAmount, $vente->getTotalAmount(), 2) < 0;
        $facture->finalizeCreanceStatus($isCreance);
        if ($isCreance && bccomp($paidAmount, $vente->getTotalAmount(), 2) >= 0 && null !== $lastPaidAt) {
            $facture->closeCredit($lastPaidAt);
        }
        $this->factureRepository->save($facture);

        return $vente;
    }

    public function annulerVente(Vente $vente): void
    {
        if ($vente->isCancelled()) {
            throw new \DomainException('Sale is already cancelled.');
        }

        $facture = $this->factureRepository->findByVenteId($vente->getId());
        if (null === $facture) {
            throw new \DomainException('No invoice found for this sale.');
        }

        $vente->cancel();
        $this->venteRepository->save($vente);

        $this->eventDispatcher->dispatch(new VenteAnnulee(
            $vente->getId(),
            $vente->getReference(),
            $this->operationLines($vente->getLines()->toArray()),
            $facture->getId(),
        ));
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function initierCommande(array $payload): Commande
    {
        $acheteur = Acheteur::fromArray($payload['acheteur'] ?? []);
        $commande = new Commande($acheteur);
        $commande->setCreatedAt($this->parseCreatedAt($payload['created_at'] ?? null));

        foreach ($this->normalizeLines($payload['lines'] ?? []) as $line) {
            $commande->addLine($line['variant_id'], $line['label'], $line['quantity'], $line['unit_price']);
        }

        if ($commande->getLines()->isEmpty()) {
            throw new \InvalidArgumentException('An order must contain at least one line.');
        }

        $this->commandeRepository->save($commande);

        return $commande;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function confirmerCommande(Commande $commande, array $payload = []): void
    {
        $this->stockAvailabilityService->assertCanConfirm($commande);
        $commande->confirm($this->parseDeliveryDate($payload['delivery_date'] ?? null));
        $this->commandeRepository->save($commande);

        $this->eventDispatcher->dispatch(new CommandeConfirmee(
            $commande->getId(),
            $commande->getReference(),
            $this->operationLines($commande->getLines()->toArray()),
            $commande->getTotalAmount(),
            $commande->getAcheteur()->toArray(),
        ));
    }

    public function annulerCommande(Commande $commande): void
    {
        $stockWasImpacted = $commande->wasConfirmed();
        $commande->cancel();
        $this->commandeRepository->save($commande);

        $this->eventDispatcher->dispatch(new CommandeAnnulee(
            $commande->getId(),
            $commande->getReference(),
            $this->operationLines($commande->getLines()->toArray()),
            $stockWasImpacted,
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $rawLines
     *
     * @return list<array{variant_id: Uuid, label: string, quantity: string, unit_price: string}>
     */
    private function normalizeLines(array $rawLines): array
    {
        $lines = [];
        foreach ($rawLines as $raw) {
            if (empty($raw['variant_id']) || empty($raw['quantity'])) {
                throw new \InvalidArgumentException('Each line requires variant_id and quantity.');
            }

            $variant = $this->variantRepository->findById(Uuid::fromString((string) $raw['variant_id']));
            if (null === $variant) {
                throw new \InvalidArgumentException('Unknown variant: ' . $raw['variant_id']);
            }

            $unitPrice = isset($raw['unit_price']) && '' !== $raw['unit_price']
                ? (string) $raw['unit_price']
                : $variant->getDefaultPrice();

            $lines[] = [
                'variant_id' => $variant->getId(),
                'label' => $this->variantLabel($variant),
                'quantity' => (string) $raw['quantity'],
                'unit_price' => $unitPrice,
            ];
        }

        return $lines;
    }

    /**
     * @param list<\App\Commerce\Domain\Entity\VenteLine|\App\Commerce\Domain\Entity\CommandeLine> $lines
     *
     * @return list<OperationLine>
     */
    private function operationLines(array $lines): array
    {
        return array_map(static fn ($line) => new OperationLine(
            (string) $line->getVariantId(),
            $line->getLabel(),
            $line->getQuantity(),
            $line->getUnitPrice(),
            $line->getLineTotal(),
        ), $lines);
    }

    private function variantLabel(ProductVariant $variant): string
    {
        return sprintf(
            '%s — %s · %s',
            $variant->getProduct()->getName(),
            $variant->getUnitOfMeasure()->getLabel(),
            $variant->getSaleMode()->value,
        );
    }

    private function parseCreatedAt(mixed $value): \DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return new \DateTimeImmutable();
        }

        try {
            return new \DateTimeImmutable((string) $value);
        } catch (\Exception) {
            throw new \InvalidArgumentException('Invalid created_at datetime.');
        }
    }

    private function parseDeliveryDate(mixed $value): \DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            throw new \InvalidArgumentException('delivery_date is required when confirming an order.');
        }

        try {
            return new \DateTimeImmutable((string) $value);
        } catch (\Exception) {
            throw new \InvalidArgumentException('Invalid delivery_date.');
        }
    }
}
