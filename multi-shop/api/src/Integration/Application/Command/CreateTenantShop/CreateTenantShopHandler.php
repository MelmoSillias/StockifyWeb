<?php

namespace App\Integration\Application\Command\CreateTenantShop;

use App\Integration\Application\Service\CreateTenantShopAdminService;
use App\Integration\Application\Service\TenantFeatureGuard;
use App\Integration\Domain\Entity\TenantAccount;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use App\Shop\Application\Command\CreateShop\CreateShopCommand;
use App\Shop\Application\Command\CreateShop\CreateShopHandler;
use App\Shop\Domain\Entity\Shop;

final readonly class CreateTenantShopResult
{
    public function __construct(
        public Shop $shop,
        public ?string $adminEmail = null,
        public ?string $temporaryPassword = null,
    ) {
    }
}

final readonly class CreateTenantShopCommand
{
    public function __construct(
        public string $externalAccountId,
        public string $name,
        public string $slug,
        public ?string $currency = null,
        public ?string $address = null,
        public ?string $phone = null,
        public ?string $adminEmail = null,
        public ?string $adminPassword = null,
        public bool $createAdmin = true,
    ) {
    }

    public static function fromPayload(string $externalAccountId, array $payload): self
    {
        return new self(
            externalAccountId: $externalAccountId,
            name: (string) ($payload['name'] ?? ''),
            slug: (string) ($payload['slug'] ?? ''),
            currency: isset($payload['currency']) ? (string) $payload['currency'] : null,
            address: isset($payload['address']) ? (string) $payload['address'] : null,
            phone: isset($payload['phone']) ? (string) $payload['phone'] : null,
            adminEmail: isset($payload['admin_email']) ? (string) $payload['admin_email'] : null,
            adminPassword: isset($payload['admin_password']) ? (string) $payload['admin_password'] : null,
            createAdmin: !array_key_exists('create_admin', $payload) || (bool) $payload['create_admin'],
        );
    }
}

final class CreateTenantShopHandler
{
    public function __construct(
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
        private readonly CreateShopHandler $createShopHandler,
        private readonly CreateTenantShopAdminService $createTenantShopAdminService,
        private readonly TenantFeatureGuard $tenantFeatureGuard,
    ) {
    }

    public function handle(CreateTenantShopCommand $command): CreateTenantShopResult
    {
        $account = $this->requireActiveAccount($command->externalAccountId);
        $this->tenantFeatureGuard->assertCanCreateShop($account);

        $shop = $this->createShopHandler->handle(new CreateShopCommand(
            name: $command->name,
            slug: $command->slug,
            currency: $command->currency,
            address: $command->address,
            phone: $command->phone,
        ));

        $shop->setTenantAccountId($account->getId());

        $adminEmail = null;
        $temporaryPassword = null;

        if ($command->createAdmin) {
            $email = $command->adminEmail ?? $this->defaultAdminEmail($command);
            $admin = $this->createTenantShopAdminService->create(
                $shop,
                $account->getId(),
                $email,
                $command->name,
                $command->adminPassword,
            );
            $adminEmail = $admin['user']->getEmail();
            $temporaryPassword = $admin['temporary_password'];
        }

        return new CreateTenantShopResult($shop, $adminEmail, $temporaryPassword);
    }

    private function defaultAdminEmail(CreateTenantShopCommand $command): string
    {
        return sprintf('admin+%s@tenant.stockify.local', $command->slug);
    }

    private function requireActiveAccount(string $externalAccountId): TenantAccount
    {
        $account = $this->tenantAccountRepository->findByExternalAccountId($externalAccountId);
        if (null === $account) {
            throw new \InvalidArgumentException('Tenant account not found.');
        }

        if ($account->isSuspended()) {
            throw new \DomainException('Tenant account is suspended.');
        }

        return $account;
    }
}
