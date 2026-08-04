<?php

namespace App\Inventory\Application\EventListener;

use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use App\Commerce\Domain\Event\CommandeAnnulee;
use App\Inventory\Application\Service\StockMovementService;
use App\Livraison\Domain\Repository\BonDeLivraisonRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Uid\Uuid;

#[AsEventListener(event: CommandeAnnulee::class)]
final class RestockOnCommandeAnnulee
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly StockMovementService $stockMovementService,
        private readonly BonDeLivraisonRepositoryInterface $bonDeLivraisonRepository,
    ) {
    }

    public function __invoke(CommandeAnnulee $event): void
    {
        if (!$event->stockWasImpacted()) {
            return;
        }

        $sourceRef = (string) $event->commandeId();
        $shipped = $this->bonDeLivraisonRepository->sumShippedQuantitiesByCommandeId($event->commandeId());

        foreach ($event->lines() as $line) {
            if (null === $line->variantId || '' === $line->variantId) {
                continue;
            }

            $quantity = $shipped[$line->variantId] ?? $line->quantity;
            if (bccomp($quantity, '0', 3) <= 0) {
                continue;
            }

            $variant = $this->variantRepository->findById(Uuid::fromString($line->variantId));
            if (null === $variant) {
                continue;
            }

            $this->stockMovementService->restock(
                $variant,
                $quantity,
                'Annulation commande ' . $event->reference(),
                $sourceRef,
            );
        }
    }
}
