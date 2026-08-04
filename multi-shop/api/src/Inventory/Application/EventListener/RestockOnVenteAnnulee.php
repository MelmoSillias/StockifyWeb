<?php

namespace App\Inventory\Application\EventListener;

use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use App\Commerce\Domain\Event\VenteAnnulee;
use App\Inventory\Application\Service\StockMovementService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Uid\Uuid;

#[AsEventListener(event: VenteAnnulee::class)]
final class RestockOnVenteAnnulee
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly StockMovementService $stockMovementService,
    ) {
    }

    public function __invoke(VenteAnnulee $event): void
    {
        $sourceRef = (string) $event->venteId();

        foreach ($event->lines() as $line) {
            if (null === $line->variantId || '' === $line->variantId) {
                continue;
            }

            $variant = $this->variantRepository->findById(Uuid::fromString($line->variantId));
            if (null === $variant) {
                continue;
            }

            $this->stockMovementService->restock(
                $variant,
                $line->quantity,
                'Annulation vente ' . $event->reference(),
                $sourceRef,
            );
        }
    }
}
