<?php

namespace App\AccessAudit\Application\Service;

use App\AccessAudit\Domain\Repository\UserPermissionRepositoryInterface;
use App\AccessAudit\Domain\Repository\UserRoleRepositoryInterface;
use App\IdentityAccess\Domain\Entity\User;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class PermissionResolverService
{
    private const CACHE_TTL = 300;

    public function __construct(
        private readonly UserRoleRepositoryInterface $userRoleRepository,
        private readonly UserPermissionRepositoryInterface $userPermissionRepository,
        private readonly CacheInterface $cache,
    ) {
    }

    /** @return list<string> */
    public function resolveRoleCodes(User $user): array
    {
        $userRoles = $this->userRoleRepository->findByUser($user);

        return array_values(array_unique(array_map(
            static fn ($ur) => $ur->getRole()->getCode(),
            $userRoles,
        )));
    }

    /** @return list<string> */
    public function resolvePermissions(User $user): array
    {
        $cacheKey = 'user_permissions_'.(string) $user->getId();

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($user): array {
            $item->expiresAfter(self::CACHE_TTL);

            $permissions = [];

            foreach ($this->userRoleRepository->findByUser($user) as $userRole) {
                foreach ($userRole->getRole()->getPermissionCodes() as $code) {
                    $permissions[$code] = true;
                }
            }

            foreach ($this->userPermissionRepository->findByUser($user) as $override) {
                $code = $override->getPermission()->getCode();
                if ($override->isGranted()) {
                    $permissions[$code] = true;
                } else {
                    unset($permissions[$code]);
                }
            }

            ksort($permissions);

            return array_keys($permissions);
        });
    }

    public function hasPermission(User $user, string $permission): bool
    {
        return in_array($permission, $this->resolvePermissions($user), true);
    }

    public function invalidateCache(User $user): void
    {
        $this->cache->delete('user_permissions_'.(string) $user->getId());
    }
}
