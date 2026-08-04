<?php

namespace App\IdentityAccess\Infrastructure\Security;

final class LoginContextHolder
{
    private ?string $shopSlug = null;

    public function setShopSlug(?string $shopSlug): void
    {
        $this->shopSlug = null !== $shopSlug && '' !== trim($shopSlug) ? strtolower(trim($shopSlug)) : null;
    }

    public function getShopSlug(): ?string
    {
        return $this->shopSlug;
    }

    public function clear(): void
    {
        $this->shopSlug = null;
    }
}
