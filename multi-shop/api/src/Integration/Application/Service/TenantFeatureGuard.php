<?php

namespace App\Integration\Application\Service;

use App\Integration\Domain\Entity\TenantAccount;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use App\Shop\Domain\Entity\Shop;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Uid\Uuid;

final class TenantFeatureGuard
{
    public function __construct(
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
        private readonly TenantEntitlementResolver $entitlementResolver,
        private readonly EntitlementPullService $entitlementPullService,
    ) {
    }

    public function assertCanCreateShop(TenantAccount $account): void
    {
        $this->entitlementPullService->ensureFresh($account);

        if (!$this->entitlementResolver->canCreateShop($account)) {
            throw new \DomainException(sprintf(
                'Shop quota exceeded (max %d).',
                $this->entitlementResolver->getQuota($account, 'max_shops', 1),
            ));
        }
    }

    public function assertFeatureForShop(Shop $shop, string $featureCode): void
    {
        $tenantAccountId = $shop->getTenantAccountId();
        if (null === $tenantAccountId) {
            return;
        }

        $account = $this->tenantAccountRepository->findById($tenantAccountId);
        if (null === $account) {
            return;
        }

        $this->entitlementPullService->ensureFresh($account);

        if (!$this->entitlementResolver->hasFeature($account, $featureCode)) {
            throw new AccessDeniedHttpException(sprintf('Feature "%s" is not enabled for this tenant.', $featureCode));
        }
    }

    public function assertFeatureForTenantAccountId(?Uuid $tenantAccountId, string $featureCode): void
    {
        if (null === $tenantAccountId) {
            return;
        }

        $account = $this->tenantAccountRepository->findById($tenantAccountId);
        if (null === $account) {
            return;
        }

        $this->entitlementPullService->ensureFresh($account);

        if (!$this->entitlementResolver->hasFeature($account, $featureCode)) {
            throw new AccessDeniedHttpException(sprintf('Feature "%s" is not enabled for this tenant.', $featureCode));
        }
    }
}
