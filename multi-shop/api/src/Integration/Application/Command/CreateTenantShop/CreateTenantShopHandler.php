<?php

namespace App\Integration\Application\Command\CreateTenantShop;

use App\Integration\Application\Service\CreateTenantShopAdminService;
use App\Integration\Application\Service\TenantFeatureGuard;
use App\Integration\Domain\Entity\TenantAccount;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use App\Shop\Application\Command\CreateShop\CreateShopCommand;
use App\Shop\Application\Command\CreateShop\CreateShopHandler;
use App\Shop\Domain\Entity\Shop;
use App\Shop\Domain\Repository\ShopRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CreateTenantShopResult
{
    public function __construct(
        public Shop $shop,
        public ?string $adminEmail = null,
        public ?string $adminUsername = null,
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
        public ?string $identityId = null,
        public ?string $adminFirstName = null,
        public ?string $adminLastName = null,
        public ?string $adminPhone = null,
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
            adminFirstName: isset($payload['admin_first_name']) ? (string) $payload['admin_first_name'] : null,
            adminLastName: isset($payload['admin_last_name']) ? (string) $payload['admin_last_name'] : null,
            adminPhone: isset($payload['admin_phone']) ? (string) $payload['admin_phone'] : null,
        );
    }
}

final class CreateTenantShopHandler
{
    public function __construct(
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
        private readonly CreateShopHandler $createShopHandler,
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly CreateTenantShopAdminService $createTenantShopAdminService,
        private readonly TenantFeatureGuard $tenantFeatureGuard,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function handle(CreateTenantShopCommand $command): CreateTenantShopResult
    {
        $account = $this->requireActiveAccount($command->externalAccountId);
        $slug = strtolower(trim($command->slug));

        $existing = '' !== $slug ? $this->shopRepository->findBySlug($slug) : null;
        if (null !== $existing) {
            $existingTenantId = $existing->getTenantAccountId();
            if (null !== $existingTenantId && $existingTenantId->equals($account->getId())) {
                return $this->resumeExistingShop($existing, $account, $command);
            }

            throw new \InvalidArgumentException('Ce slug est déjà utilisé.');
        }

        $this->tenantFeatureGuard->assertCanCreateShop($account);

        return $this->entityManager->wrapInTransaction(function () use ($command, $account): CreateTenantShopResult {
            $shop = $this->createShopHandler->handle(new CreateShopCommand(
                name: $command->name,
                slug: $command->slug,
                currency: $command->currency,
                address: $command->address,
                phone: $command->phone,
            ));

            $shop->setTenantAccountId($account->getId());
            $this->shopRepository->save($shop, false);

            return $this->withOptionalAdmin($shop, $account, $command);
        });
    }

    private function resumeExistingShop(
        Shop $shop,
        TenantAccount $account,
        CreateTenantShopCommand $command,
    ): CreateTenantShopResult {
        return $this->withOptionalAdmin($shop, $account, $command);
    }

    private function withOptionalAdmin(
        Shop $shop,
        TenantAccount $account,
        CreateTenantShopCommand $command,
    ): CreateTenantShopResult {
        $adminEmail = null;
        $adminUsername = null;
        $temporaryPassword = null;

        if ($command->createAdmin) {
            $admin = $this->createTenantShopAdminService->create(
                $shop,
                $account->getId(),
                $command->adminEmail,
                $command->name,
                $command->adminPassword,
                $command->slug.'-admin',
                $command->identityId,
                $command->adminFirstName,
                $command->adminLastName,
                $command->adminPhone,
            );
            $adminEmail = $admin['user']->getEmail();
            $adminUsername = $admin['user']->getUsername();
            $temporaryPassword = $admin['temporary_password'];
            $this->entityManager->flush();
        }

        return new CreateTenantShopResult($shop, $adminEmail, $adminUsername, $temporaryPassword);
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
