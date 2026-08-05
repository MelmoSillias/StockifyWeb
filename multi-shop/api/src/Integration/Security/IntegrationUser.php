<?php

namespace App\Integration\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final class IntegrationUser implements UserInterface
{
    /** @param list<string> $roles */
    public function __construct(
        private readonly string $identifier = 'integration-control-plane',
        private readonly array $roles = ['ROLE_INTEGRATION'],
    ) {
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }
}
