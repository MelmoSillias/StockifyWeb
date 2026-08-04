<?php

namespace App\Inventory\Application\Service;

use App\Catalog\Domain\Entity\ProductVariant;
use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use App\Commerce\Domain\Entity\Commande;
use App\Commerce\Domain\Repository\CommandeRepositoryInterface;
use App\Inventory\Domain\Repository\StockLotRepositoryInterface;
use App\Livraison\Domain\Repository\BonDeLivraisonRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class StockAvailabilityService
{
    public function __construct(
        private readonly StockLotRepositoryInterface $stockLotRepository,
        private readonly CommandeRepositoryInterface $commandeRepository,
        private readonly BonDeLivraisonRepositoryInterface $bonDeLivraisonRepository,
        private readonly ProductVariantRepositoryInterface $variantRepository,
    ) {
    }

    /**
     * @return array<string, string> variantId => reserved quantity
     */
    public function getReservedQuantitiesByVariant(): array
    {
        $totals = [];

        foreach ($this->commandeRepository->findActiveForStockReservation() as $commande) {
            $shipped = $this->bonDeLivraisonRepository->sumShippedQuantitiesByCommandeId($commande->getId());

            foreach ($commande->getLines() as $line) {
                if ($line->isLibre()) {
                    continue;
                }

                $variantId = (string) $line->getVariantId();
                $remaining = bcsub($line->getQuantity(), $shipped[$variantId] ?? '0', 3);

                if (bccomp($remaining, '0', 3) <= 0) {
                    continue;
                }

                $totals[$variantId] = bcadd($totals[$variantId] ?? '0', $remaining, 3);
            }
        }

        return $totals;
    }

    public function getPhysicalQuantity(ProductVariant $variant): string
    {
        return $this->stockLotRepository->sumAvailableStock($variant);
    }

    public function getReservedQuantity(ProductVariant $variant): string
    {
        $reserved = $this->getReservedQuantitiesByVariant();

        return $reserved[(string) $variant->getId()] ?? '0.000';
    }

    public function getSellableQuantity(ProductVariant $variant): string
    {
        $sellable = bcsub($this->getPhysicalQuantity($variant), $this->getReservedQuantity($variant), 3);

        return bccomp($sellable, '0', 3) < 0 ? '0.000' : $sellable;
    }

    public function assertCanConfirm(Commande $commande): void
    {
        foreach ($commande->getLines() as $line) {
            if ($line->isLibre()) {
                continue;
            }

            $variant = $this->variantRepository->findById($line->getVariantId());
            if (null === $variant) {
                throw new \DomainException('Unknown variant in order line.');
            }

            $sellable = $this->getSellableQuantity($variant);
            if (bccomp($line->getQuantity(), $sellable, 3) > 0) {
                throw new \DomainException(sprintf(
                    'Insufficient stock for "%s": %s available (including reservations), %s requested.',
                    $line->getLabel(),
                    $sellable,
                    $line->getQuantity(),
                ));
            }
        }
    }

    /**
     * @return array<string, string> variantId => sellable quantity
     */
    public function getSellableQuantitiesByVariant(): array
    {
        $physical = $this->stockLotRepository->sumAvailableStockByVariant();
        $reserved = $this->getReservedQuantitiesByVariant();
        $sellable = [];

        foreach ($physical as $variantId => $quantity) {
            $value = bcsub($quantity, $reserved[$variantId] ?? '0', 3);
            $sellable[$variantId] = bccomp($value, '0', 3) < 0 ? '0.000' : $value;
        }

        foreach ($reserved as $variantId => $quantity) {
            if (!isset($sellable[$variantId])) {
                $sellable[$variantId] = '0.000';
            }
        }

        return $sellable;
    }
}
