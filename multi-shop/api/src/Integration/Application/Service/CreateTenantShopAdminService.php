<?php

namespace App\Integration\Application\Service;

use App\AccessAudit\Domain\Entity\UserRole;
use App\AccessAudit\Domain\Repository\RoleRepositoryInterface;
use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use App\Shop\Application\Service\ShopPasswordGenerator;
use App\Shop\Domain\Entity\Shop;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class CreateTenantShopAdminService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ShopPasswordGenerator $passwordGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{user: User, temporary_password: string|null, user_provided_password: bool}
     */
    public function create(Shop $shop, Uuid $tenantAccountId, string $adminEmail, string $accountName, ?string $password = null): array
    {
        $email = strtolower(trim($adminEmail));
        if ('' === $email) {
            throw new \InvalidArgumentException('Admin email is required.');
        }

        if (null !== $this->userRepository->findByEmail($email)) {
            throw new \InvalidArgumentException('Admin email already registered.');
        }

        $username = $this->resolveUsername($email, $shop->getSlug());
        $userProvidedPassword = null !== $password && '' !== trim($password);
        if ($userProvidedPassword) {
            $plainPassword = trim($password);
            if (strlen($plainPassword) < 8) {
                throw new \InvalidArgumentException('Admin password must be at least 8 characters.');
            }
        } else {
            $plainPassword = $this->passwordGenerator->generate()->plainValue();
        }

        $nameParts = $this->splitAccountName($accountName);

        $user = new User($email, $username, 'placeholder', $nameParts['first'], $nameParts['last']);
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->activate();
        if (!$userProvidedPassword) {
            $user->requirePasswordChange();
        }
        $user->assignToShop($shop->getId());
        $user->assignToTenantAccount($tenantAccountId);

        $role = $this->roleRepository->findByCode('gerant');
        if (null === $role) {
            throw new \RuntimeException('Default shop admin role "gerant" not found.');
        }

        $userRole = new UserRole($user, $role);
        $user->getUserRoles()->add($userRole);
        $user->syncSymfonyRoles(['gerant']);

        $this->entityManager->persist($user);
        $this->entityManager->persist($userRole);

        return [
            'user' => $user,
            'temporary_password' => $userProvidedPassword ? null : $plainPassword,
            'user_provided_password' => $userProvidedPassword,
        ];
    }

    private function resolveUsername(string $email, string $shopSlug): string
    {
        $localPart = strtolower((string) strtok($email, '@'));
        $base = preg_replace('/[^a-z0-9._-]/', '', $localPart) ?: $shopSlug.'-admin';
        $username = $base;
        $suffix = 1;

        while (null !== $this->userRepository->findByUsername($username)) {
            $username = $base.'-'.$suffix;
            ++$suffix;
        }

        return $username;
    }

    /** @return array{first: string, last: string} */
    private function splitAccountName(string $accountName): array
    {
        $trimmed = trim($accountName);
        if ('' === $trimmed) {
            return ['first' => 'Admin', 'last' => 'Shop'];
        }

        $parts = preg_split('/\s+/', $trimmed, 2) ?: [];

        return [
            'first' => $parts[0],
            'last' => $parts[1] ?? 'Admin',
        ];
    }
}
