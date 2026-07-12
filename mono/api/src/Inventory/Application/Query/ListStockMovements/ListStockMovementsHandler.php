<?php

namespace App\Inventory\Application\Query\ListStockMovements;

use App\Inventory\Application\Dto\StockMovementDetailDto;
use App\Inventory\Domain\Entity\StockMovement;
use App\Inventory\Domain\Repository\StockMovementRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class ListStockMovementsHandler
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $stockMovementRepository,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function handle(ListStockMovementsQuery $query): array
    {
        $variantUuid = null;
        if (null !== $query->variantId && '' !== $query->variantId && Uuid::isValid($query->variantId)) {
            $variantUuid = Uuid::fromString($query->variantId);
        }

        $movements = $this->stockMovementRepository->findAll($variantUuid);

        return array_map(
            static function (StockMovement $movement): array {
                $variant = $movement->getVariant();
                $product = $variant->getProduct();
                $category = $product->getCategory();
                $unit = $variant->getUnitOfMeasure();
                $variantLabel = sprintf('%s · %s', $unit->getLabel(), $variant->getSaleMode()->value);

                return (new StockMovementDetailDto(
                    id: (string) $movement->getId(),
                    variantId: (string) $variant->getId(),
                    variantLabel: $variantLabel,
                    sku: $variant->getSku(),
                    productId: (string) $product->getId(),
                    productName: $product->getName(),
                    categoryId: $category ? (string) $category->getId() : null,
                    categoryName: $category?->getName(),
                    type: $movement->getType()->value,
                    direction: $movement->getDirection()->value,
                    quantity: $movement->getQuantity(),
                    occurredAt: $movement->getOccurredAt()->format(\DateTimeInterface::ATOM),
                    allocations: array_map(
                        static fn ($allocation) => [
                            'lot_id' => (string) $allocation->getLot()->getId(),
                            'quantity' => $allocation->getQuantity(),
                        ],
                        $movement->getAllocations()->toArray(),
                    ),
                ))->toArray();
            },
            $movements,
        );
    }
}
