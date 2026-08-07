<?php

namespace App\IdentityAccess\Infrastructure\Security;

use App\IdentityAccess\Domain\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class IdentityAwareUserPasswordHasher implements UserPasswordHasherInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $inner,
        private readonly AuthUserResolver $authUserResolver,
    ) {
    }

    public function hashPassword(PasswordAuthenticatedUserInterface $user, #[\SensitiveParameter] string $plainPassword): string
    {
        return $this->inner->hashPassword($user, $plainPassword);
    }

    public function isPasswordValid(PasswordAuthenticatedUserInterface $user, #[\SensitiveParameter] string $plainPassword): bool
    {
        if ($user instanceof User && null !== $user->getIdentityId()) {
            return $this->authUserResolver->verifyPassword($user, $plainPassword);
        }

        return $this->inner->isPasswordValid($user, $plainPassword);
    }

    public function needsRehash(PasswordAuthenticatedUserInterface $user): bool
    {
        if ($user instanceof User && null !== $user->getIdentityId()) {
            return false;
        }

        return $this->inner->needsRehash($user);
    }
}
