<?php

namespace App\Integration\Application\Command\DeleteAccount;

use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use App\Shop\Domain\Repository\ShopRepositoryInterface;

final readonly class DeleteAccountCommand
{
    public function __construct(
        public string $externalAccountId,
    ) {
    }
}

final class DeleteAccountHandler
{
    public function __construct(
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
        private readonly ShopRepositoryInterface $shopRepository,
    ) {
    }

    public function handle(DeleteAccountCommand $command): void
    {
        $account = $this->tenantAccountRepository->findByExternalAccountId($command->externalAccountId);
        if (null === $account) {
            throw new \InvalidArgumentException('Tenant account not found.');
        }

        $shops = $this->shopRepository->findByTenantAccountId($account->getId());
        if ([] !== $shops) {
            throw new \DomainException('Cannot delete tenant account with existing shops.');
        }

        $this->tenantAccountRepository->remove($account);
    }
}
