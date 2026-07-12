<?php

namespace App\IdentityAccess\Application\Service;

use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterUserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function register(string $email, string $password, string $firstName, string $lastName, ?string $username = null): User
    {
        if (null !== $this->userRepository->findByEmail($email)) {
            throw new \InvalidArgumentException('Email already registered.');
        }

        $resolvedUsername = $this->resolveUniqueUsername($username, $email);
        if (null !== $this->userRepository->findByUsername($resolvedUsername)) {
            throw new \InvalidArgumentException('Username already registered.');
        }

        $user = new User($email, $resolvedUsername, '', $firstName, $lastName);
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, $password));
        $user->activate();

        $this->userRepository->save($user);

        return $user;
    }

    private function resolveUniqueUsername(?string $preferredUsername, string $email): string
    {
        $username = strtolower(trim((string) $preferredUsername));
        if ('' !== $username) {
            return $username;
        }

        $localPart = strtolower((string) strtok($email, '@'));
        if ('' === $localPart) {
            return strtolower(bin2hex(random_bytes(6)));
        }

        return preg_replace('/[^a-z0-9._-]/', '', $localPart) ?: strtolower(bin2hex(random_bytes(6)));
    }
}
