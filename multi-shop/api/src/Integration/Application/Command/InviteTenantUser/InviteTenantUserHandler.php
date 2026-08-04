<?php

namespace App\Integration\Application\Command\InviteTenantUser;

use App\Integration\Application\Service\CreateTenantShopAdminService;
use App\Integration\Domain\Entity\TenantAccount;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use App\Shop\Domain\Repository\ShopRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final readonly class InviteTenantUserCommand
{
    public function __construct(
        public string $externalAccountId,
        public string $email,
        public ?string $shopId = null,
        public string $roleCode = 'gerant',
        public string $firstName = 'Invited',
        public string $lastName = 'User',
    ) {
    }

    public static function fromPayload(string $externalAccountId, array $payload): self
    {
        return new self(
            externalAccountId: $externalAccountId,
            email: (string) ($payload['email'] ?? ''),
            shopId: isset($payload['shop_id']) ? (string) $payload['shop_id'] : null,
            roleCode: (string) ($payload['role'] ?? 'gerant'),
            firstName: (string) ($payload['first_name'] ?? 'Invited'),
            lastName: (string) ($payload['last_name'] ?? 'User'),
        );
    }
}

final readonly class InviteTenantUserResult
{
    public function __construct(
        public string $userId,
        public string $email,
        public string $temporaryPassword,
    ) {
    }
}

final class InviteTenantUserHandler
{
    public function __construct(
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly CreateTenantShopAdminService $createTenantShopAdminService,
    ) {
    }

    public function handle(InviteTenantUserCommand $command): InviteTenantUserResult
    {
        $account = $this->requireActiveAccount($command->externalAccountId);
        $shop = $this->resolveShop($account, $command->shopId);

        $result = $this->createTenantShopAdminService->create(
            $shop,
            $account->getId(),
            $command->email,
            trim($command->firstName.' '.$command->lastName),
        );

        return new InviteTenantUserResult(
            (string) $result['user']->getId(),
            $result['user']->getEmail(),
            $result['temporary_password'],
        );
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

    private function resolveShop(TenantAccount $account, ?string $shopId): \App\Shop\Domain\Entity\Shop
    {
        if (null !== $shopId && '' !== $shopId) {
            $shop = $this->shopRepository->findById(Uuid::fromString($shopId));
            if (null === $shop) {
                throw new \InvalidArgumentException('Shop not found.');
            }
            if (null === $shop->getTenantAccountId() || !$shop->getTenantAccountId()->equals($account->getId())) {
                throw new \InvalidArgumentException('Shop does not belong to tenant.');
            }

            return $shop;
        }

        $shops = $this->shopRepository->findByTenantAccountId($account->getId());
        if ([] === $shops) {
            throw new \InvalidArgumentException('No shop found for tenant.');
        }

        return $shops[0];
    }
}
