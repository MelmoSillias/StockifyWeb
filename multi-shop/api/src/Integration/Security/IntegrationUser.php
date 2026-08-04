<?php

namespace App\Integration\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final class IntegrationUser implements UserInterface
{
    public function getRoles(): array
    {
        return ['ROLE_INTEGRATION'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return 'integration-control-plane';
    }
}
