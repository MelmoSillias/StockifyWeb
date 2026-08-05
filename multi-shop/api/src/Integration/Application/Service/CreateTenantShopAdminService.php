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
     * @return array{user: User, temporary_password: string|null, user_provided_password: bool, membership_only: bool}
     */
    public function create(
        Shop $shop,
        Uuid $tenantAccountId,
        ?string $adminEmail,
        string $accountName,
        ?string $password = null,
        ?string $preferredUsername = null,
        ?string $identityId = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $phone = null,
    ): array {
        $email = self::normalizeEmail($adminEmail);

        if (null !== $email) {
            $existing = $this->userRepository->findByEmail($email);
            if (null !== $existing) {
                return $this->attachExistingTenantUser($existing, $shop, $tenantAccountId, $identityId);
            }
        }

        $username = $this->resolveUsername(
            $email,
            $preferredUsername ?? $shop->getSlug().'-admin',
            $shop->getSlug(),
        );
        $userProvidedPassword = null !== $password && '' !== trim($password);
        if ($userProvidedPassword) {
            $plainPassword = trim($password);
            if (strlen($plainPassword) < 8) {
                throw new \InvalidArgumentException('Admin password must be at least 8 characters.');
            }
        } else {
            $plainPassword = $this->passwordGenerator->generate()->plainValue();
        }

        $resolvedFirstName = null !== $firstName && '' !== trim($firstName) ? trim($firstName) : null;
        $resolvedLastName = null !== $lastName && '' !== trim($lastName) ? trim($lastName) : null;
        if (null === $resolvedFirstName || null === $resolvedLastName) {
            $nameParts = $this->splitAccountName($accountName);
            $resolvedFirstName ??= $nameParts['first'];
            $resolvedLastName ??= $nameParts['last'];
        }

        $user = new User($email, $username, 'placeholder', $resolvedFirstName, $resolvedLastName);
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, $plainPassword));
        if (null !== $phone && '' !== trim($phone)) {
            $user->setPhone(trim($phone));
        }
        $user->activate();
        if (!$userProvidedPassword) {
            $user->requirePasswordChange();
        }
        $user->assignToShop($shop->getId());
        $user->assignToTenantAccount($tenantAccountId);
        $this->applyIdentityId($user, $identityId);

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
            'membership_only' => false,
        ];
    }

    /**
     * @return array{user: User, temporary_password: string|null, user_provided_password: bool, membership_only: bool}
     */
    private function attachExistingTenantUser(User $user, Shop $shop, Uuid $tenantAccountId, ?string $identityId = null): array
    {
        if ($user->isPlatformOwner()) {
            throw new \InvalidArgumentException('Platform owner cannot be assigned as shop admin.');
        }

        $existingTenantId = $user->getTenantAccountId();
        if (null !== $existingTenantId && !$existingTenantId->equals($tenantAccountId)) {
            throw new \InvalidArgumentException('Admin email already registered.');
        }

        if (null === $existingTenantId) {
            $user->assignToTenantAccount($tenantAccountId);
        }

        $makePrimary = [] === $user->getShopIds();
        $user->addShopMembership($shop->getId(), $makePrimary);
        $this->applyIdentityId($user, $identityId);
        $this->userRepository->save($user);

        return [
            'user' => $user,
            'temporary_password' => null,
            'user_provided_password' => true,
            'membership_only' => true,
        ];
    }

    private function resolveUsername(?string $email, string $preferredUsername, string $shopSlug): string
    {
        if (null !== $email) {
            $localPart = strtolower((string) strtok($email, '@'));
            $base = preg_replace('/[^a-z0-9._-]/', '', $localPart) ?: $shopSlug.'-admin';
        } else {
            $base = preg_replace('/[^a-z0-9._-]/', '', strtolower($preferredUsername)) ?: $shopSlug.'-admin';
        }

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

    private static function normalizeEmail(?string $email): ?string
    {
        if (null === $email) {
            return null;
        }

        $normalized = strtolower(trim($email));

        return '' === $normalized ? null : $normalized;
    }

    private function applyIdentityId(User $user, ?string $identityId): void
    {
        if (null === $identityId || '' === trim($identityId)) {
            return;
        }

        if (null !== $user->getIdentityId()) {
            return;
        }

        $user->setIdentityId(Uuid::fromString(trim($identityId)));
    }
}
