<?php

namespace App\Integration\Application\Service;

use App\Integration\Domain\Entity\TenantAccount;
use App\Shop\Domain\Repository\ShopRepositoryInterface;

final class TenantEntitlementResolver
{
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository,
    ) {
    }

    /** @return list<string> */
    public function getFeatures(TenantAccount $account): array
    {
        $snapshot = $account->getEntitlementsSnapshot();
        if (isset($snapshot['features']) && is_array($snapshot['features'])) {
            return array_values(array_unique(array_map('strval', $snapshot['features'])));
        }

        return [];
    }

    public function hasFeature(TenantAccount $account, string $featureCode): bool
    {
        return in_array($featureCode, $this->getFeatures($account), true);
    }

    public function getQuota(TenantAccount $account, string $quotaKey, int $default = 0): int
    {
        $snapshot = $account->getEntitlementsSnapshot();

        if (isset($snapshot['quotas'][$quotaKey])) {
            return max(0, (int) $snapshot['quotas'][$quotaKey]);
        }

        if (isset($snapshot[$quotaKey])) {
            return max(0, (int) $snapshot[$quotaKey]);
        }

        if ('max_shops' === $quotaKey && isset($snapshot['max_shops'])) {
            return max(0, (int) $snapshot['max_shops']);
        }

        return $default;
    }

    public function countShops(TenantAccount $account): int
    {
        return count($this->shopRepository->findByTenantAccountId($account->getId()));
    }

    public function canCreateShop(TenantAccount $account): bool
    {
        $maxShops = $this->getQuota($account, 'max_shops', 1);

        return $this->countShops($account) < $maxShops;
    }

    public function remainingShopQuota(TenantAccount $account): int
    {
        $maxShops = $this->getQuota($account, 'max_shops', 1);

        return max(0, $maxShops - $this->countShops($account));
    }
}
