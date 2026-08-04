<?php

namespace App\Integration\Application\Command\UpdateEntitlements;

use App\Integration\Domain\Entity\TenantAccount;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;

final class UpdateEntitlementsHandler
{
    public function __construct(
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
    ) {
    }

    public function handle(UpdateEntitlementsCommand $command): TenantAccount
    {
        $account = $this->requireAccount($command->externalAccountId);
        $account->updateEntitlements($command->entitlements);
        $this->tenantAccountRepository->save($account);

        return $account;
    }

    private function requireAccount(string $externalAccountId): TenantAccount
    {
        $account = $this->tenantAccountRepository->findByExternalAccountId($externalAccountId);
        if (null === $account) {
            throw new \InvalidArgumentException('Tenant account not found.');
        }

        return $account;
    }
}
