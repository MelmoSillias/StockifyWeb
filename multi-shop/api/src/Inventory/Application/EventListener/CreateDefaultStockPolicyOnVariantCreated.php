<?php

namespace App\Inventory\Application\EventListener;

use App\Catalog\Domain\Event\ProductVariantCreated;
use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use App\Inventory\Domain\Entity\StockPolicy;
use App\Inventory\Domain\Enum\StockPolicyStrategy;
use App\Inventory\Domain\Repository\StockPolicyRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ProductVariantCreated::class)]
final class CreateDefaultStockPolicyOnVariantCreated
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly StockPolicyRepositoryInterface $stockPolicyRepository,
    ) {
    }

    public function __invoke(ProductVariantCreated $event): void
    {
        $variant = $this->variantRepository->findById($event->variantId());
        if (null === $variant) {
            return;
        }

        $policy = new StockPolicy($variant, StockPolicyStrategy::Fifo);
        $this->stockPolicyRepository->save($policy);
    }
}
