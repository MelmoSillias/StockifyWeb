<?php

namespace App\IdentityAccess\Security;

use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Enum\UserStatus;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserEnabledChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (UserStatus::Suspended === $user->getStatus()) {
            throw new CustomUserMessageAccountStatusException('Your account has been suspended.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (UserStatus::Active !== $user->getStatus()) {
            throw new CustomUserMessageAccountStatusException('Your account is not active.');
        }
    }
}
