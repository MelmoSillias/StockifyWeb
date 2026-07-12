<?php

namespace App\Catalog\Application\Query\ListVariantsCatalog;

use App\Catalog\Application\Dto\VariantCatalogItemDto;
use App\Catalog\Domain\Entity\ProductVariant;
use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use App\Inventory\Domain\Repository\StockLotRepositoryInterface;

final class ListVariantsCatalogHandler
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly StockLotRepositoryInterface $stockLotRepository,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function handle(ListVariantsCatalogQuery $query): array
    {
        $variants = $this->variantRepository->findAllWithCatalogRelations();
        $availableByVariant = $this->stockLotRepository->sumAvailableStockByVariant();

        return array_map(
            function (ProductVariant $variant) use ($availableByVariant): array {
                $variantId = (string) $variant->getId();
                $product = $variant->getProduct();
                $category = $product->getCategory();
                $unit = $variant->getUnitOfMeasure();
                $available = $availableByVariant[$variantId] ?? '0.000';
                $threshold = $variant->getAlertThreshold();
                $isLowStock = null !== $threshold && '' !== $threshold
                    && bccomp($available, $threshold, 3) < 0;
                $variantLabel = sprintf('%s · %s', $unit->getLabel(), $variant->getSaleMode()->value);

                return (new VariantCatalogItemDto(
                    id: $variantId,
                    sku: $variant->getSku(),
                    label: sprintf('%s — %s', $product->getName(), $variantLabel),
                    productId: (string) $product->getId(),
                    productName: $product->getName(),
                    categoryId: $category ? (string) $category->getId() : null,
                    categoryName: $category?->getName(),
                    unitOfMeasureId: (string) $unit->getId(),
                    unitCode: $unit->getCode(),
                    unitLabel: $unit->getLabel(),
                    saleMode: $variant->getSaleMode()->value,
                    available: $available,
                    isLowStock: $isLowStock,
                ))->toArray();
            },
            $variants,
        );
    }
}
