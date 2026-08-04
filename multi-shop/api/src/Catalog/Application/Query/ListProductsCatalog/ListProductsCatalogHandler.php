<?php

namespace App\Catalog\Application\Query\ListProductsCatalog;

use App\Catalog\Application\Dto\ProductDetailDto;
use App\Catalog\Application\Dto\VariantDetailDto;
use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Entity\ProductVariant;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use App\Inventory\Domain\Repository\StockLotRepositoryInterface;

final class ListProductsCatalogHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly StockLotRepositoryInterface $stockLotRepository,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function handle(ListProductsCatalogQuery $query): array
    {
        $products = $this->productRepository->findAll();
        $variants = $this->variantRepository->findAllWithCatalogRelations();
        $availableByVariant = $this->stockLotRepository->sumAvailableStockByVariant();
        $lotCountByVariant = $this->stockLotRepository->countLotsByVariant();

        /** @var array<string, list<ProductVariant>> $variantsByProduct */
        $variantsByProduct = [];
        foreach ($variants as $variant) {
            $productId = (string) $variant->getProduct()->getId();
            $variantsByProduct[$productId][] = $variant;
        }

        return array_map(
            function (Product $product) use ($variantsByProduct, $availableByVariant, $lotCountByVariant): array {
                $productId = (string) $product->getId();
                $productVariants = $variantsByProduct[$productId] ?? [];
                $variantDtos = [];
                $hasLowStock = false;

                foreach ($productVariants as $variant) {
                    $variantDto = $this->mapVariant($variant, $availableByVariant, $lotCountByVariant);
                    $hasLowStock = $hasLowStock || $variantDto->isLowStock;
                    $variantDtos[] = $variantDto;
                }

                $category = $product->getCategory();

                return (new ProductDetailDto(
                    id: $productId,
                    name: $product->getName(),
                    reference: $product->getReference(),
                    description: $product->getDescription(),
                    categoryId: $category ? (string) $category->getId() : null,
                    categoryName: $category?->getName(),
                    status: $product->getStatus()->value,
                    variantCount: \count($variantDtos),
                    hasLowStock: $hasLowStock,
                    variants: $variantDtos,
                ))->toArray();
            },
            $products,
        );
    }

    /**
     * @param array<string, string> $availableByVariant
     * @param array<string, int>    $lotCountByVariant
     */
    private function mapVariant(
        ProductVariant $variant,
        array $availableByVariant,
        array $lotCountByVariant,
    ): VariantDetailDto {
        $variantId = (string) $variant->getId();
        $available = $availableByVariant[$variantId] ?? '0.000';
        $threshold = $variant->getAlertThreshold();
        $isLowStock = null !== $threshold && '' !== $threshold
            && bccomp($available, $threshold, 3) < 0;
        $unit = $variant->getUnitOfMeasure();

        return new VariantDetailDto(
            id: $variantId,
            productId: (string) $variant->getProduct()->getId(),
            sku: $variant->getSku(),
            unitOfMeasureId: (string) $unit->getId(),
            unitCode: $unit->getCode(),
            unitLabel: $unit->getLabel(),
            saleMode: $variant->getSaleMode()->value,
            defaultPrice: $variant->getDefaultPrice(),
            alertThreshold: $threshold,
            status: $variant->getStatus()->value,
            available: $available,
            lotCount: $lotCountByVariant[$variantId] ?? 0,
            isLowStock: $isLowStock,
        );
    }
}
