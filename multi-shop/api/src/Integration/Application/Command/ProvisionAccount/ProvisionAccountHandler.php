<?php

namespace App\Integration\Application\Command\ProvisionAccount;

use App\Integration\Domain\Entity\TenantAccount;
use App\Integration\Domain\Repository\IntegrationRequestLogRepositoryInterface;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;

final class ProvisionAccountHandler
{
    public function __construct(
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
        private readonly IntegrationRequestLogRepositoryInterface $requestLogRepository,
    ) {
    }

    public function handle(ProvisionAccountCommand $command): TenantAccount
    {
        $externalAccountId = trim($command->externalAccountId);
        if ('' === $externalAccountId) {
            throw new \InvalidArgumentException('external_account_id is required.');
        }

        if (null !== $command->idempotencyKey && '' !== trim($command->idempotencyKey)) {
            $existingLog = $this->requestLogRepository->findByIdempotencyKey(
                $command->idempotencyKey,
                'POST',
                '/integration/v1/accounts',
            );
            if (null !== $existingLog && null !== $existingLog->getResponseBody()) {
                $cachedId = $existingLog->getResponseBody()['id'] ?? null;
                if (is_string($cachedId)) {
                    $cached = $this->tenantAccountRepository->findByExternalAccountId($externalAccountId);
                    if (null !== $cached) {
                        return $cached;
                    }
                }
            }
        }

        $existing = $this->tenantAccountRepository->findByExternalAccountId($externalAccountId);
        if (null !== $existing) {
            return $existing;
        }

        $account = new TenantAccount($externalAccountId, $command->entitlements);
        $account->markProvisioned();
        $this->tenantAccountRepository->save($account);

        return $account;
    }
}
