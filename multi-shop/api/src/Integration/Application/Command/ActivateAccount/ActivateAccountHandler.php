<?php

namespace App\Integration\Application\Command\ActivateAccount;

use App\Integration\Domain\Entity\TenantAccount;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;

final readonly class ActivateAccountCommand
{
    public function __construct(
        public string $externalAccountId,
    ) {
    }
}

final class ActivateAccountHandler
{
    public function __construct(
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
    ) {
    }

    public function handle(ActivateAccountCommand $command): TenantAccount
    {
        $account = $this->requireAccount($command->externalAccountId);
        $account->activate();
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
