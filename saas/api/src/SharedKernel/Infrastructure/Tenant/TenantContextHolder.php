<?php

namespace App\SharedKernel\Infrastructure\Tenant;

use App\SharedKernel\Domain\ValueObject\TenantContext;

final class TenantContextHolder
{
    private ?TenantContext $context = null;

    public function set(TenantContext $context): void
    {
        $this->context = $context;
    }

    public function get(): TenantContext
    {
        if (null === $this->context) {
            throw new \RuntimeException('Tenant context is not available.');
        }

        return $this->context;
    }

    public function has(): bool
    {
        return null !== $this->context;
    }
}
