<?php

namespace App\Integration\Application\Command\SuspendAccount;

use App\Integration\Domain\Entity\TenantAccount;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;

final readonly class SuspendAccountCommand
{
    public function __construct(
        public string $externalAccountId,
    ) {
    }
}

final class SuspendAccountHandler
{
    public function __construct(
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
    ) {
    }

    public function handle(SuspendAccountCommand $command): TenantAccount
    {
        $account = $this->requireAccount($command->externalAccountId);
        $account->suspend();
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
