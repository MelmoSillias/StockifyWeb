<?php

namespace App\SharedKernel\Infrastructure\Shop;

use App\SharedKernel\Domain\ValueObject\ShopContext;

final class ShopContextHolder
{
    private ?ShopContext $context = null;

    public function set(ShopContext $context): void
    {
        $this->context = $context;
    }

    public function get(): ?ShopContext
    {
        return $this->context;
    }

    public function require(): ShopContext
    {
        if (null === $this->context) {
            throw new \RuntimeException('No active shop context.');
        }

        return $this->context;
    }

    public function clear(): void
    {
        $this->context = null;
    }
}
