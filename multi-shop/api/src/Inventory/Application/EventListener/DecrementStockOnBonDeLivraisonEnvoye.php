<?php

namespace App\Inventory\Application\EventListener;

use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use App\Inventory\Application\Service\StockMovementService;
use App\Inventory\Domain\Enum\MovementType;
use App\Inventory\Domain\Exception\InsufficientStockException;
use App\Livraison\Domain\Event\BonDeLivraisonEnvoye;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Uid\Uuid;

#[AsEventListener(event: BonDeLivraisonEnvoye::class)]
final class DecrementStockOnBonDeLivraisonEnvoye
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly StockMovementService $stockMovementService,
    ) {
    }

    public function __invoke(BonDeLivraisonEnvoye $event): void
    {
        $sourceRef = (string) $event->bonDeLivraisonId();

        foreach ($event->lines() as $line) {
            if (null === $line->variantId || '' === $line->variantId) {
                continue;
            }

            $variant = $this->variantRepository->findById(Uuid::fromString($line->variantId));
            if (null === $variant) {
                continue;
            }

            try {
                $this->stockMovementService->stockOut(
                    $variant,
                    $line->quantity,
                    MovementType::Sale,
                    'Bon de livraison ' . $event->reference(),
                    null,
                    $sourceRef,
                );
            } catch (InsufficientStockException) {
                // Expedition is not blocked by stock shortage in MVP.
            }
        }
    }
}
