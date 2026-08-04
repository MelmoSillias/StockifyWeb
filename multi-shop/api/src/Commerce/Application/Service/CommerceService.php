<?php

namespace App\Commerce\Application\Service;

use App\Catalog\Domain\Entity\ProductVariant;
use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use App\Commerce\Domain\Entity\Commande;
use App\Commerce\Domain\Entity\Devis;
use App\Commerce\Domain\Entity\Vente;
use App\Commerce\Domain\Enum\CommerceLineType;
use App\Commerce\Domain\Event\CommandeAnnulee;
use App\Commerce\Domain\Event\CommandeConfirmee;
use App\Commerce\Domain\Event\OperationLine;
use App\Commerce\Domain\Event\VenteAnnulee;
use App\Commerce\Domain\Event\VenteRealisee;
use App\Commerce\Domain\Repository\CommandeRepositoryInterface;
use App\Commerce\Domain\Repository\DevisRepositoryInterface;
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
        private readonly DevisRepositoryInterface $devisRepository,
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
            $vente->addLine($line['variant_id'], $line['label'], $line['quantity'], $line['unit_price'], $line['line_type']);
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
            $commande->addLine($line['variant_id'], $line['label'], $line['quantity'], $line['unit_price'], $line['line_type']);
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
     * @param array<string, mixed> $payload
     */
    public function creerDevis(array $payload): Devis
    {
        $acheteur = Acheteur::fromArray($payload['acheteur'] ?? []);
        $createdAt = $this->parseCreatedAt($payload['created_at'] ?? null);
        $devis = new Devis($acheteur, $createdAt);

        foreach ($this->normalizeLines($payload['lines'] ?? []) as $line) {
            $devis->addLine($line['variant_id'], $line['label'], $line['quantity'], $line['unit_price'], $line['line_type']);
        }

        if ($devis->getLines()->isEmpty()) {
            throw new \InvalidArgumentException('A quote must contain at least one line.');
        }

        $devis->setValidUntil($this->parseValidUntil($payload['valid_until'] ?? null));
        $this->devisRepository->save($devis);

        return $devis;
    }

    public function annulerDevis(Devis $devis): void
    {
        $devis->cancel();
        $this->devisRepository->save($devis);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function convertirDevisEnVente(Devis $devis, array $payload): Vente
    {
        $devis->refreshStatus();
        $ventePayload = [
            'acheteur' => $devis->getAcheteur()->toArray(),
            'lines' => $this->devisLinesPayload($devis),
            'created_at' => $payload['created_at'] ?? null,
        ];

        if (!empty($payload['initial_payment']) && \is_array($payload['initial_payment'])) {
            $ventePayload['initial_payment'] = $payload['initial_payment'];
        }

        $vente = $this->realiserVente($ventePayload);
        $devis->markConvertedToVente($vente->getId());
        $this->devisRepository->save($devis);

        return $vente;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function convertirDevisEnCommande(Devis $devis, array $payload): Commande
    {
        $devis->refreshStatus();
        $commandePayload = [
            'acheteur' => $devis->getAcheteur()->toArray(),
            'lines' => $this->devisLinesPayload($devis),
            'created_at' => $payload['created_at'] ?? null,
        ];

        $commande = $this->initierCommande($commandePayload);

        if (!empty($payload['initial_payment']) && \is_array($payload['initial_payment'])) {
            $payment = $payload['initial_payment'];
            if (!empty($payment['amount'])) {
                $paiementPayload = [
                    'commande_id' => (string) $commande->getId(),
                    'amount' => (string) $payment['amount'],
                ];
                if (!empty($payment['mode_de_paiement_id'])) {
                    $paiementPayload['mode_de_paiement_id'] = (string) $payment['mode_de_paiement_id'];
                }
                if (!empty($payment['paid_at'])) {
                    $paiementPayload['paid_at'] = (string) $payment['paid_at'];
                }
                $this->paiementService->enregistrer($paiementPayload);
            }
        }

        $confirm = filter_var($payload['confirm'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($confirm) {
            $this->confirmerCommande($commande, ['delivery_date' => $payload['delivery_date'] ?? null]);
        }

        $devis->markConvertedToCommande($commande->getId());
        $this->devisRepository->save($devis);

        return $commande;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function devisLinesPayload(Devis $devis): array
    {
        return array_map(static function ($line) {
            $payload = [
                'quantity' => $line->getQuantity(),
                'unit_price' => $line->getUnitPrice(),
            ];

            if ($line->isLibre()) {
                $payload['label'] = $line->getLabel();
            } else {
                $payload['variant_id'] = (string) $line->getVariantId();
            }

            return $payload;
        }, $devis->getLines()->toArray());
    }

    /**
     * @param array<int, array<string, mixed>> $rawLines
     *
     * @return list<array{variant_id: ?Uuid, label: string, quantity: string, unit_price: string, line_type: CommerceLineType}>
     */
    private function normalizeLines(array $rawLines): array
    {
        $lines = [];
        foreach ($rawLines as $raw) {
            if (!\is_array($raw)) {
                throw new \InvalidArgumentException('Each line must be an object.');
            }

            $hasVariant = !empty($raw['variant_id']);
            $hasLabel = isset($raw['label']) && '' !== trim((string) $raw['label']);

            if ($hasVariant) {
                if (empty($raw['quantity'])) {
                    throw new \InvalidArgumentException('Each line requires variant_id and quantity.');
                }

                $variant = $this->variantRepository->findById(Uuid::fromString((string) $raw['variant_id']));
                if (null === $variant) {
                    throw new \InvalidArgumentException('Unknown variant: ' . $raw['variant_id']);
                }

                $quantity = (string) $raw['quantity'];
                if (bccomp($quantity, '0', 3) <= 0) {
                    throw new \InvalidArgumentException('Quantity must be greater than zero.');
                }

                $unitPrice = isset($raw['unit_price']) && '' !== $raw['unit_price']
                    ? (string) $raw['unit_price']
                    : $variant->getDefaultPrice();

                if (bccomp($unitPrice, '0', 2) < 0) {
                    throw new \InvalidArgumentException('Unit price cannot be negative.');
                }

                $lines[] = [
                    'variant_id' => $variant->getId(),
                    'label' => $this->variantLabel($variant),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_type' => CommerceLineType::Product,
                ];

                continue;
            }

            if ($hasLabel) {
                if (empty($raw['quantity'])) {
                    throw new \InvalidArgumentException('Each free line requires label and quantity.');
                }

                if (!isset($raw['unit_price']) || '' === (string) $raw['unit_price']) {
                    throw new \InvalidArgumentException('Each free line requires unit_price.');
                }

                $quantity = (string) $raw['quantity'];
                $unitPrice = (string) $raw['unit_price'];

                if (bccomp($quantity, '0', 3) <= 0) {
                    throw new \InvalidArgumentException('Quantity must be greater than zero.');
                }

                if (bccomp($unitPrice, '0', 2) < 0) {
                    throw new \InvalidArgumentException('Unit price cannot be negative.');
                }

                $lines[] = [
                    'variant_id' => null,
                    'label' => trim((string) $raw['label']),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_type' => CommerceLineType::Libre,
                ];

                continue;
            }

            throw new \InvalidArgumentException('Each line requires variant_id or label.');
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
            null !== $line->getVariantId() ? (string) $line->getVariantId() : null,
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

    private function parseValidUntil(mixed $value): ?\DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return null;
        }

        try {
            return new \DateTimeImmutable((string) $value);
        } catch (\Exception) {
            throw new \InvalidArgumentException('Invalid valid_until date.');
        }
    }
}
