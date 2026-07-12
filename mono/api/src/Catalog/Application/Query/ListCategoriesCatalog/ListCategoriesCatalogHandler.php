<?php

namespace App\Catalog\Application\Query\ListCategoriesCatalog;

use App\Catalog\Application\Dto\CategoryDetailDto;
use App\Catalog\Domain\Repository\ProductCategoryRepositoryInterface;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;

final class ListCategoriesCatalogHandler
{
    public function __construct(
        private readonly ProductCategoryRepositoryInterface $categoryRepository,
        private readonly ProductRepositoryInterface $productRepository,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function handle(ListCategoriesCatalogQuery $query): array
    {
        $categories = $this->categoryRepository->findAll();
        $products = $this->productRepository->findAll();

        /** @var array<string, int> $counts */
        $counts = [];
        foreach ($products as $product) {
            $category = $product->getCategory();
            if (null === $category) {
                continue;
            }
            $categoryId = (string) $category->getId();
            $counts[$categoryId] = ($counts[$categoryId] ?? 0) + 1;
        }

        return array_map(
            static function ($category) use ($counts): array {
                $categoryId = (string) $category->getId();

                return (new CategoryDetailDto(
                    id: $categoryId,
                    name: $category->getName(),
                    parentId: $category->getParent() ? (string) $category->getParent()->getId() : null,
                    sortOrder: $category->getSortOrder(),
                    status: $category->getStatus()->value,
                    productCount: $counts[$categoryId] ?? 0,
                ))->toArray();
            },
            $categories,
        );
    }
}
