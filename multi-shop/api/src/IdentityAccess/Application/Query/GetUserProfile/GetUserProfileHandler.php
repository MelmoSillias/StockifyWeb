<?php

namespace App\IdentityAccess\Application\Query\GetUserProfile;

use App\AccessAudit\Application\Service\PermissionResolverService;
use App\IdentityAccess\Domain\Entity\User;
use App\Integration\Application\Service\TenantEntitlementResolver;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use App\Shop\Domain\Entity\Shop;
use App\Shop\Domain\Repository\ShopRepositoryInterface;

final class GetUserProfileHandler
{
    public function __construct(
        private readonly PermissionResolverService $permissionResolver,
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
        private readonly TenantEntitlementResolver $entitlementResolver,
    ) {
    }

    /**
     * @return array{
     *     user: array<string, mixed>,
     *     permissions: list<string>,
     *     features: list<string>,
     *     accessible_shops: list<array<string, mixed>>
     * }
     */
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
            'features' => $this->resolveFeatures($user),
            'accessible_shops' => $this->resolveAccessibleShops($user),
        ];
    }

    /** @return list<string> */
    private function resolveFeatures(User $user): array
    {
        $tenantAccountId = $user->getTenantAccountId();
        if (null === $tenantAccountId) {
            return [];
        }

        $account = $this->tenantAccountRepository->findById($tenantAccountId);
        if (null === $account) {
            return [];
        }

        return $this->entitlementResolver->getFeatures($account);
    }

    /** @return list<array<string, mixed>> */
    private function resolveAccessibleShops(User $user): array
    {
        if ($user->isPlatformOwner()) {
            return array_map(
                fn (Shop $shop) => $this->serializeAccessibleShop($shop),
                $this->shopRepository->findAllOrderedByName(),
            );
        }

        $shops = [];
        foreach ($user->getShopIds() as $shopId) {
            $shop = $this->shopRepository->findById($shopId);
            if (null === $shop) {
                continue;
            }

            $shops[] = $this->serializeAccessibleShop($shop);
        }

        usort($shops, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $shops;
    }

    /**
     * @return array{id: string, name: string, slug: string, status: string, features: list<string>|null}
     */
    private function serializeAccessibleShop(Shop $shop): array
    {
        return [
            'id' => (string) $shop->getId(),
            'name' => $shop->getName(),
            'slug' => $shop->getSlug(),
            'status' => $shop->getStatus()->value,
            // null = no tenant → ungated (mirrors TenantFeatureGuard)
            'features' => $this->resolveFeaturesForShop($shop),
        ];
    }

    /** @return list<string>|null */
    private function resolveFeaturesForShop(Shop $shop): ?array
    {
        $tenantAccountId = $shop->getTenantAccountId();
        if (null === $tenantAccountId) {
            return null;
        }

        $account = $this->tenantAccountRepository->findById($tenantAccountId);
        if (null === $account) {
            return [];
        }

        return $this->entitlementResolver->getFeatures($account);
    }
}
