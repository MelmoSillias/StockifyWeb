<?php

namespace App\Inventory\Application\Query\GetStockAlerts;

use App\Catalog\Domain\Entity\ProductVariant;
use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use App\Inventory\Application\Dto\StockAlertDetailDto;
use App\Inventory\Domain\Repository\StockLotRepositoryInterface;

final class GetStockAlertsHandler
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly StockLotRepositoryInterface $stockLotRepository,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function handle(GetStockAlertsQuery $query): array
    {
        $variants = $this->variantRepository->findAllWithCatalogRelations();
        $availableByVariant = $this->stockLotRepository->sumAvailableStockByVariant();
        $alerts = [];

        foreach ($variants as $variant) {
            $threshold = $variant->getAlertThreshold();
            if (null === $threshold || '' === $threshold) {
                continue;
            }

            $variantId = (string) $variant->getId();
            $available = $availableByVariant[$variantId] ?? '0.000';
            if (bccomp($available, $threshold, 3) >= 0) {
                continue;
            }

            $alerts[] = $this->mapAlert($variant, $available, $threshold)->toArray();
        }

        return $alerts;
    }

    private function mapAlert(ProductVariant $variant, string $available, string $threshold): StockAlertDetailDto
    {
        $product = $variant->getProduct();
        $category = $product->getCategory();
        $unit = $variant->getUnitOfMeasure();

        return new StockAlertDetailDto(
            variantId: (string) $variant->getId(),
            sku: $variant->getSku(),
            variantLabel: sprintf('%s · %s', $unit->getLabel(), $variant->getSaleMode()->value),
            productId: (string) $product->getId(),
            productName: $product->getName(),
            categoryId: $category ? (string) $category->getId() : null,
            categoryName: $category?->getName(),
            available: $available,
            alertThreshold: $threshold,
        );
    }
}
