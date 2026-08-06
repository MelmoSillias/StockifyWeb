<?php

namespace App\Integration\Application\Service;

use App\Integration\Domain\Entity\TenantAccount;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use App\Shop\Domain\Entity\Shop;
use App\Shop\Domain\Repository\ShopRepositoryInterface;
use App\SharedKernel\Infrastructure\Shop\ShopContextHolder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Uid\Uuid;

final class TenantFeatureGuard
{
    public function __construct(
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
        private readonly TenantEntitlementResolver $entitlementResolver,
        private readonly EntitlementPullService $entitlementPullService,
        private readonly ShopContextHolder $shopContextHolder,
        private readonly ShopRepositoryInterface $shopRepository,
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

    public function assertCanCreateUser(TenantAccount $account): void
    {
        $this->entitlementPullService->ensureFresh($account);

        if (!$this->entitlementResolver->canCreateUser($account)) {
            throw new \DomainException(sprintf(
                'User quota exceeded (max %d).',
                $this->entitlementResolver->getQuota($account, 'max_users', 3),
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

    public function assertFeatureForActiveShop(string $featureCode): void
    {
        $context = $this->shopContextHolder->get();
        if (null === $context) {
            return;
        }

        $shop = $this->shopRepository->findById($context->getShopId());
        if (null === $shop) {
            return;
        }

        $this->assertFeatureForShop($shop, $featureCode);
    }

    public function assertCanCreateUserForActiveShop(): void
    {
        $context = $this->shopContextHolder->get();
        if (null === $context) {
            return;
        }

        $shop = $this->shopRepository->findById($context->getShopId());
        if (null === $shop) {
            return;
        }

        $tenantAccountId = $shop->getTenantAccountId();
        if (null === $tenantAccountId) {
            return;
        }

        $account = $this->tenantAccountRepository->findById($tenantAccountId);
        if (null === $account) {
            return;
        }

        $this->assertCanCreateUser($account);
    }
}
