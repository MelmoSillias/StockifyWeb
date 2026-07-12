<?php

namespace App\Catalog\Domain\Event;

use App\SharedKernel\Domain\Event\DomainEventInterface;
use App\SharedKernel\Domain\ValueObject\TenantScope;
use Symfony\Component\Uid\Uuid;

final readonly class ProductVariantCreated implements DomainEventInterface
{
    public function __construct(
        private Uuid $variantId,
        private TenantScope $tenantScope,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function variantId(): Uuid
    {
        return $this->variantId;
    }

    public function tenantScope(): TenantScope
    {
        return $this->tenantScope;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
