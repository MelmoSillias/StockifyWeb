<?php

namespace App\Inventory\Application\EventListener;

use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use App\Commerce\Domain\Event\VenteRealisee;
use App\Inventory\Application\Service\StockMovementService;
use App\Inventory\Domain\Enum\MovementType;
use App\Inventory\Domain\Exception\InsufficientStockException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Uid\Uuid;

#[AsEventListener(event: VenteRealisee::class)]
final class DecrementStockOnVenteRealisee
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly StockMovementService $stockMovementService,
    ) {
    }

    public function __invoke(VenteRealisee $event): void
    {
        $sourceRef = (string) $event->venteId();

        foreach ($event->lines() as $line) {
            $variant = $this->variantRepository->findById(Uuid::fromString($line->variantId));
            if (null === $variant) {
                continue;
            }

            try {
                $this->stockMovementService->stockOut(
                    $variant,
                    $line->quantity,
                    MovementType::Sale,
                    'Vente ' . $event->reference(),
                    null,
                    $sourceRef,
                );
            } catch (InsufficientStockException) {
                // MVP: the sale is already recorded; stock reservation is out of scope.
            }
        }
    }
}
