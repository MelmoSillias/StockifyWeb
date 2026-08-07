<?php

namespace App\IdentityAccess\Infrastructure\Security;

use App\IdentityAccess\Domain\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * Delegates password verification for identity-linked users to the Control Plane.
 * Uses LoginContextHolder because PasswordHasherInterface::verify() has no user argument.
 */
final class IdentityLinkedPasswordHasher implements PasswordHasherInterface
{
    use CheckPasswordLengthTrait;

    public const IDENTITY_DELEGATED_HASH = '{identity-delegated}';

    private readonly NativePasswordHasher $inner;

    public function __construct(
        private readonly AuthUserResolver $authUserResolver,
        private readonly LoginContextHolder $loginContextHolder,
    ) {
        $this->inner = new NativePasswordHasher();
    }

    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        return $this->inner->hash($plainPassword);
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

        if (self::IDENTITY_DELEGATED_HASH === $hashedPassword || '' === $hashedPassword) {
            return false;
        }

        return $this->inner->verify($hashedPassword, $plainPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        if (self::IDENTITY_DELEGATED_HASH === $hashedPassword || '' === $hashedPassword) {
            return false;
        }

        return $this->inner->needsRehash($hashedPassword);
    }
}
