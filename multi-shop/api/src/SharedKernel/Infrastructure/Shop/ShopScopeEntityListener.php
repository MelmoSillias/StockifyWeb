<?php

namespace App\SharedKernel\Infrastructure\Shop;

use App\SharedKernel\Domain\Contract\ShopScopedInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
final class ShopScopeEntityListener
{
    public function __construct(
        private readonly ShopContextHolder $shopContextHolder,
    ) {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof ShopScopedInterface) {
            return;
        }

        if (null !== $entity->getShopId()) {
            return;
        }

        $context = $this->shopContextHolder->get();
        if (null === $context) {
            return;
        }

        $entity->setShopId($context->getShopId());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof ShopScopedInterface) {
            return;
        }

        $context = $this->shopContextHolder->get();
        if (null === $context) {
            return;
        }

        $activeShopId = $context->getShopId();
        if (null !== $entity->getShopId() && !$entity->getShopId()->equals($activeShopId)) {
            throw new \DomainException('Cross-shop entity update is not allowed.');
        }
    }
}
