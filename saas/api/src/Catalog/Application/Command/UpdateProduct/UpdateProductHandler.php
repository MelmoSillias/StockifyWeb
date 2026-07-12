<?php

namespace App\Catalog\Application\Command\UpdateProduct;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductCategoryRepositoryInterface;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\SharedKernel\Infrastructure\Tenant\TenantContextHolder;
use Symfony\Component\Uid\Uuid;

final class UpdateProductHandler
{
    public function __construct(
        private readonly TenantContextHolder $tenantContextHolder,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductCategoryRepositoryInterface $categoryRepository,
    ) {
    }

    public function handle(UpdateProductCommand $command): Product
    {
        $product = $this->getProductForCurrentShop($command->productId);

        if (null !== $command->name && '' !== $command->name) {
            $product->rename($command->name);
        }

        if (null !== $command->reference || null !== $command->description) {
            $product->updateDetails($command->reference, $command->description);
        }

        if (null !== $command->categoryId && '' !== $command->categoryId) {
            $category = $this->categoryRepository->findById(Uuid::fromString($command->categoryId));
            $product->assignCategory($category);
        }

        $this->productRepository->save($product);

        return $product;
    }

    private function getProductForCurrentShop(string $id): Product
    {
        $product = $this->productRepository->findById(Uuid::fromString($id));
        if (null === $product || !$product->getShopId()->equals($this->tenantContextHolder->get()->getShop()->getId())) {
            throw new \DomainException('Product not found');
        }

        return $product;
    }
}
