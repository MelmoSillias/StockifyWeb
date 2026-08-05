<?php

namespace App\Onboarding\Application\Service;

use App\Integration\Application\Command\CreateTenantShop\CreateTenantShopCommand;
use App\Integration\Application\Command\CreateTenantShop\CreateTenantShopHandler;
use App\Integration\Application\Command\CreateTenantShop\CreateTenantShopResult;
use App\Integration\Application\Command\ProvisionAccount\ProvisionAccountCommand;
use App\Integration\Application\Command\ProvisionAccount\ProvisionAccountHandler;
use App\Integration\Application\Query\GetAccountUsage\GetAccountUsageHandler;
use App\Integration\Application\Query\GetAccountUsage\GetAccountUsageQuery;
use App\Integration\Application\Service\UsageWebhookDispatcher;
use App\Shop\Domain\Repository\ShopRepositoryInterface;

final class LocalSignupProvisioner
{
    public function __construct(
        private readonly ProvisionAccountHandler $provisionAccountHandler,
        private readonly CreateTenantShopHandler $createTenantShopHandler,
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly GetAccountUsageHandler $getAccountUsageHandler,
        private readonly UsageWebhookDispatcher $usageWebhookDispatcher,
    ) {
    }

    /**
     * @param array{features?: list<string>, quotas?: array<string, int|float>} $entitlements
     */
    public function provision(
        string $externalAccountId,
        string $accountName,
        string $accountSlug,
        string $adminEmail,
        ?string $adminPassword,
        array $entitlements,
        ?string $identityId = null,
        ?string $adminFirstName = null,
        ?string $adminLastName = null,
        ?string $adminPhone = null,
        ?string $shopPhone = null,
        ?string $shopAddress = null,
    ): CreateTenantShopResult {
        $this->provisionAccountHandler->handle(new ProvisionAccountCommand(
            externalAccountId: $externalAccountId,
            idempotencyKey: $externalAccountId,
            entitlements: $entitlements,
        ));

        $result = $this->createTenantShopHandler->handle(new CreateTenantShopCommand(
            externalAccountId: $externalAccountId,
            name: $accountName,
            slug: $accountSlug,
            address: $shopAddress,
            phone: $shopPhone,
            adminEmail: $adminEmail,
            adminPassword: $adminPassword,
            identityId: $identityId,
            adminFirstName: $adminFirstName,
            adminLastName: $adminLastName,
            adminPhone: $adminPhone,
        ));

        $this->shopRepository->save($result->shop);

        $usage = $this->getAccountUsageHandler->handle(new GetAccountUsageQuery($externalAccountId));
        $this->usageWebhookDispatcher->dispatchUsageUpdated($externalAccountId, [
            'shops_count' => (int) ($usage['shops_count'] ?? 0),
            'users_count' => (int) ($usage['users_count'] ?? 0),
        ]);

        return $result;
    }
}
