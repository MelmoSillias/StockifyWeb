<?php

namespace App\Catalog\Application\Command\CreateProduct;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductCategoryRepositoryInterface;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class CreateProductHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductCategoryRepositoryInterface $categoryRepository,
    ) {
    }

    public function handle(CreateProductCommand $command): Product
    {
        if ('' === trim($command->name)) {
            throw new \InvalidArgumentException('name is required');
        }

        $product = new Product($command->name);
        $product->updateDetails($command->reference, $command->description);

        if (null !== $command->categoryId && '' !== $command->categoryId) {
            $category = $this->categoryRepository->findById(Uuid::fromString($command->categoryId));
            $product->assignCategory($category);
        }

        $this->productRepository->save($product);

        return $product;
    }
}
