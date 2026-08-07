<?php

namespace App\IdentityAccess\Infrastructure\Security;

use App\IdentityAccess\Domain\Entity\User;

final class LoginContextHolder
{
    private ?string $shopSlug = null;
    private ?string $password = null;
    private ?User $authenticatingUser = null;

    public function setShopSlug(?string $shopSlug): void
    {
        $this->shopSlug = null !== $shopSlug && '' !== trim($shopSlug) ? strtolower(trim($shopSlug)) : null;
    }

    public function getShopSlug(): ?string
    {
        return $this->shopSlug;
    }

    public function setPassword(?string $password): void
    {
        $this->password = is_string($password) && '' !== $password ? $password : null;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setAuthenticatingUser(?User $user): void
    {
        $this->authenticatingUser = $user;
    }

    public function getAuthenticatingUser(): ?User
    {
        return $this->authenticatingUser;
    }

    public function clear(): void
    {
        $this->shopSlug = null;
        $this->password = null;
        $this->authenticatingUser = null;
    }
}
