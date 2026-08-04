<?php

namespace App\Catalog\Domain\Event;

use App\SharedKernel\Domain\Event\DomainEventInterface;
use Symfony\Component\Uid\Uuid;

final readonly class ProductVariantCreated implements DomainEventInterface
{
    public function __construct(
        private Uuid $variantId,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function variantId(): Uuid
    {
        return $this->variantId;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
