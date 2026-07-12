<?php

namespace App\Catalog\Application\Command\CreateProductVariant;

use App\Catalog\Domain\Entity\ProductVariant;
use App\Catalog\Domain\Event\ProductVariantCreated;
use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use App\Catalog\Domain\Repository\UnitOfMeasureRepositoryInterface;
use App\SharedKernel\Application\Event\DomainEventDispatcherInterface;
use App\SharedKernel\Domain\ValueObject\TenantScope;
use App\SharedKernel\Infrastructure\Tenant\TenantContextHolder;
use Symfony\Component\Uid\Uuid;

final class CreateProductVariantHandler
{
    public function __construct(
        private readonly TenantContextHolder $tenantContextHolder,
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly UnitOfMeasureRepositoryInterface $unitOfMeasureRepository,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handle(CreateProductVariantCommand $command): ProductVariant
    {
        $context = $this->tenantContextHolder->get();
        $unit = $this->unitOfMeasureRepository->findById(Uuid::fromString($command->unitOfMeasureId));
        if (null === $unit) {
            throw new \InvalidArgumentException('Invalid unit_of_measure_id');
        }

        $variant = new ProductVariant(
            $context->getAccount()->getId(),
            $context->getShop()->getId(),
            $command->product,
            $command->sku,
            $unit,
            $command->saleMode,
        );

        if (null !== $command->defaultPrice) {
            $variant->setDefaultPrice($command->defaultPrice);
        }
        if (null !== $command->alertThreshold) {
            $variant->setAlertThreshold($command->alertThreshold);
        }

        $this->variantRepository->save($variant);

        $this->eventDispatcher->dispatch(new ProductVariantCreated(
            $variant->getId(),
            new TenantScope($context->getAccount()->getId(), $context->getShop()->getId()),
        ));

        return $variant;
    }
}
