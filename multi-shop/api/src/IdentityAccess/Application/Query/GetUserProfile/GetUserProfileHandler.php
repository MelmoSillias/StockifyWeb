<?php

namespace App\IdentityAccess\Application\Query\GetUserProfile;

use App\AccessAudit\Application\Service\PermissionResolverService;
use App\IdentityAccess\Domain\Entity\User;
use App\Shop\Domain\Repository\ShopRepositoryInterface;

final class GetUserProfileHandler
{
    public function __construct(
        private readonly PermissionResolverService $permissionResolver,
        private readonly ShopRepositoryInterface $shopRepository,
    ) {
    }

    /** @return array{user: array<string, mixed>, permissions: list<string>, accessible_shops: list<array<string, mixed>>} */
    public function handle(GetUserProfileQuery $query): array
    {
        $user = $query->user;
        $roleCodes = $this->permissionResolver->resolveRoleCodes($user);
        $permissions = $this->permissionResolver->resolvePermissions($user);

        return [
            'user' => [
                'id' => (string) $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'status' => $user->getStatus()->value,
                'roles' => $roleCodes,
                'symfony_roles' => $user->getRoles(),
                'is_platform_owner' => $user->isPlatformOwner(),
                'shop_id' => null !== $user->getShopId() ? (string) $user->getShopId() : null,
                'tenant_account_id' => null !== $user->getTenantAccountId() ? (string) $user->getTenantAccountId() : null,
                'must_change_password' => $user->mustChangePassword(),
                'last_login_at' => $user->getLastLoginAt()?->format(\DateTimeInterface::ATOM),
                'created_at' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ],
            'permissions' => $permissions,
            'accessible_shops' => $this->resolveAccessibleShops($user),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function resolveAccessibleShops(User $user): array
    {
        if ($user->isPlatformOwner()) {
            return array_map(
                static fn ($shop) => [
                    'id' => (string) $shop->getId(),
                    'name' => $shop->getName(),
                    'slug' => $shop->getSlug(),
                    'status' => $shop->getStatus()->value,
                ],
                $this->shopRepository->findAllOrderedByName(),
            );
        }

        if (null !== $user->getTenantAccountId()) {
            return array_map(
                static fn ($shop) => [
                    'id' => (string) $shop->getId(),
                    'name' => $shop->getName(),
                    'slug' => $shop->getSlug(),
                    'status' => $shop->getStatus()->value,
                ],
                $this->shopRepository->findByTenantAccountId($user->getTenantAccountId()),
            );
        }

        if (null === $user->getShopId()) {
            return [];
        }

        $shop = $this->shopRepository->findById($user->getShopId());
        if (null === $shop) {
            return [];
        }

        return [[
            'id' => (string) $shop->getId(),
            'name' => $shop->getName(),
            'slug' => $shop->getSlug(),
            'status' => $shop->getStatus()->value,
        ]];
    }
}
