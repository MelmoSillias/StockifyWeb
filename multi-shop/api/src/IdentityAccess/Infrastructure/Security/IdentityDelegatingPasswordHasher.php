<?php

namespace App\IdentityAccess\Infrastructure\Security;

use App\IdentityAccess\Domain\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class IdentityDelegatingPasswordHasher implements PasswordHasherInterface
{
    use CheckPasswordLengthTrait;

    public function __construct(
        private readonly PasswordHasherInterface $autoHasher,
        private readonly AuthUserResolver $authUserResolver,
        private readonly LoginContextHolder $loginContextHolder,
    ) {
    }

    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        return $this->autoHasher->hash($plainPassword);
    }

    public function verify(string $hashedPassword, #[\SensitiveParameter] string $plainPassword): bool
    {
        if ($this->isPasswordTooLong($plainPassword)) {
            return false;
        }

        $user = $this->loginContextHolder->getAuthenticatingUser();
        if ($user instanceof User && null !== $user->getIdentityId()) {
            return $this->authUserResolver->verifyPassword($user, $plainPassword);
        }

        return $this->autoHasher->verify($hashedPassword, $plainPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return $this->autoHasher->needsRehash($hashedPassword);
    }
}
