<?php

namespace App\IdentityAccess\Infrastructure\Security;

use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Infrastructure\Persistence\Doctrine\DoctrineUserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class IdentityAccessUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private readonly DoctrineUserRepository $userRepository,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->loadUserByIdentifier($identifier);
        if (!$user instanceof User) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $reloaded = $this->userRepository->find($user->getId());
        if (!$reloaded instanceof User) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $user->getUserIdentifier()));
        }

        return $reloaded;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        $this->userRepository->upgradePassword($user, $newHashedPassword);
    }
}
