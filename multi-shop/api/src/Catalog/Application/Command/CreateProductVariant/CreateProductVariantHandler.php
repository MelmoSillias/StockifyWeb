<?php

namespace App\Catalog\Application\Command\CreateProductVariant;

use App\Catalog\Domain\Entity\ProductVariant;
use App\Catalog\Domain\Event\ProductVariantCreated;
use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use App\Catalog\Domain\Repository\UnitOfMeasureRepositoryInterface;
use App\SharedKernel\Application\Event\DomainEventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

final class CreateProductVariantHandler
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly UnitOfMeasureRepositoryInterface $unitOfMeasureRepository,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handle(CreateProductVariantCommand $command): ProductVariant
    {
        $unit = $this->unitOfMeasureRepository->findById(Uuid::fromString($command->unitOfMeasureId));
        if (null === $unit) {
            throw new \InvalidArgumentException('Invalid unit_of_measure_id');
        }

        $variant = new ProductVariant(
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

        $this->eventDispatcher->dispatch(new ProductVariantCreated($variant->getId()));

        return $variant;
    }
}
