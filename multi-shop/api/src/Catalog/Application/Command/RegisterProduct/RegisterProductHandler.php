<?php

namespace App\Catalog\Application\Command\RegisterProduct;

use App\Catalog\Application\Command\CreateProduct\CreateProductCommand;
use App\Catalog\Application\Command\CreateProduct\CreateProductHandler;
use App\Catalog\Application\Command\CreateProductVariant\CreateProductVariantCommand;
use App\Catalog\Application\Command\CreateProductVariant\CreateProductVariantHandler;
use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Entity\ProductVariant;
use App\Catalog\Domain\Enum\SaleMode;
use App\Inventory\Application\Service\StockMovementService;
use App\Inventory\Domain\Entity\StockLot;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Application orchestration across Catalog + Inventory.
 * Keeps domain boundaries: product/variant stay in Catalog handlers,
 * lot reception stays in Inventory StockMovementService.
 */
final class RegisterProductHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CreateProductHandler $createProductHandler,
        private readonly CreateProductVariantHandler $createProductVariantHandler,
        private readonly StockMovementService $stockMovementService,
    ) {
    }

    /**
     * @return array{product: Product, variant: ?ProductVariant, lots: list<StockLot>}
     */
    public function handle(RegisterProductCommand $command): array
    {
        if ([] !== $command->lots && null === $command->variant) {
            throw new \InvalidArgumentException('variant is required when lots are provided');
        }

        return $this->entityManager->wrapInTransaction(function () use ($command): array {
            $product = $this->createProductHandler->handle(new CreateProductCommand(
                name: $command->name,
                reference: $command->reference,
                description: $command->description,
                categoryId: $command->categoryId,
            ));

            $variant = null;
            $lots = [];

            if (null !== $command->variant) {
                $variantData = $command->variant;
                foreach (['sku', 'unit_of_measure_id', 'sale_mode'] as $field) {
                    if (empty($variantData[$field])) {
                        throw new \InvalidArgumentException(sprintf('variant.%s is required', $field));
                    }
                }

                $variant = $this->createProductVariantHandler->handle(new CreateProductVariantCommand(
                    product: $product,
                    sku: (string) $variantData['sku'],
                    unitOfMeasureId: (string) $variantData['unit_of_measure_id'],
                    saleMode: SaleMode::from((string) $variantData['sale_mode']),
                    defaultPrice: !empty($variantData['default_price']) ? (string) $variantData['default_price'] : null,
                    alertThreshold: \array_key_exists('alert_threshold', $variantData) && null !== $variantData['alert_threshold'] && '' !== $variantData['alert_threshold']
                        ? (string) $variantData['alert_threshold']
                        : null,
                ));

                foreach ($command->lots as $index => $lotData) {
                    if (empty($lotData['quantity']) || !isset($lotData['unit_cost']) || '' === (string) $lotData['unit_cost']) {
                        throw new \InvalidArgumentException(sprintf('lots[%d].quantity and lots[%d].unit_cost are required', $index, $index));
                    }

                    $expiry = !empty($lotData['expiry_date'])
                        ? new \DateTimeImmutable((string) $lotData['expiry_date'])
                        : null;

                    $lots[] = $this->stockMovementService->receiveLot(
                        $variant,
                        (string) $lotData['quantity'],
                        (string) $lotData['unit_cost'],
                        $lotData['reference'] ?? null,
                        $lotData['supplier_ref'] ?? null,
                        $expiry,
                    );
                }
            }

            return [
                'product' => $product,
                'variant' => $variant,
                'lots' => $lots,
            ];
        });
    }
}
