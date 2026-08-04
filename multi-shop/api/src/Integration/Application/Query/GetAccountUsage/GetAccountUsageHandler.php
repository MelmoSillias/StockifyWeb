<?php

namespace App\Integration\Application\Query\GetAccountUsage;

use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use App\Shop\Domain\Repository\ShopRepositoryInterface;

final readonly class GetAccountUsageQuery
{
    public function __construct(
        public string $externalAccountId,
    ) {
    }
}

final class GetAccountUsageHandler
{
    public function __construct(
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    /** @return array<string, int|string> */
    public function handle(GetAccountUsageQuery $query): array
    {
        $account = $this->tenantAccountRepository->findByExternalAccountId($query->externalAccountId);
        if (null === $account) {
            throw new \InvalidArgumentException('Tenant account not found.');
        }

        $shops = $this->shopRepository->findByTenantAccountId($account->getId());
        $userCount = 0;
        foreach ($shops as $shop) {
            $userCount += count($this->userRepository->findByShopId($shop->getId()));
        }

        return [
            'external_account_id' => $account->getExternalAccountId(),
            'shops_count' => count($shops),
            'users_count' => $userCount,
        ];
    }
}
