<?php

namespace App\Integration\Application\Service;

use App\Integration\Domain\Entity\TenantAccount;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use App\Onboarding\Application\Service\ControlPlaneException;
use App\Onboarding\Application\Service\ControlPlaneGatewayInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class EntitlementPullService
{
    private const LOCK_TTL_SECONDS = 30;

    public function __construct(
        private readonly ControlPlaneGatewayInterface $controlPlaneClient,
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly int $staleAfterSeconds = 86400,
        private readonly string $applicationSlug = 'stockify',
    ) {
    }

    public function isStale(TenantAccount $account): bool
    {
        $lastSyncedAt = $account->getLastSyncedAt();
        if (null === $lastSyncedAt) {
            return true;
        }

        $threshold = (new \DateTimeImmutable())->modify(sprintf('-%d seconds', max(1, $this->staleAfterSeconds)));

        return $lastSyncedAt < $threshold;
    }

    public function ensureFresh(TenantAccount $account): void
    {
        if (!$this->isStale($account)) {
            return;
        }

        $lockKey = 'entitlement_pull_lock_'.$account->getExternalAccountId();
        $acquired = false;

        try {
            $this->cache->get($lockKey, function (ItemInterface $item) use (&$acquired): true {
                $item->expiresAfter(self::LOCK_TTL_SECONDS);
                $acquired = true;

                return true;
            });
        } catch (\Throwable) {
            // Cache failure must never block feature checks.
            $acquired = true;
        }

        if (!$acquired) {
            return;
        }

        // Re-check after acquiring the lock: another request may have refreshed.
        if (!$this->isStale($account)) {
            return;
        }

        try {
            $snapshot = $this->controlPlaneClient->pullEntitlements(
                $account->getExternalAccountId(),
                $this->applicationSlug,
            );

            $account->updateEntitlements([
                'features' => $snapshot['features'],
                'quotas' => $snapshot['quotas'],
            ]);
            $this->tenantAccountRepository->save($account);
        } catch (ControlPlaneException $exception) {
            $this->logger->warning('Entitlement pull failed; keeping last known snapshot.', [
                'external_account_id' => $account->getExternalAccountId(),
                'error' => $exception->getMessage(),
                'status' => $exception->getCode(),
            ]);
        } catch (\Throwable $exception) {
            $this->logger->warning('Entitlement pull failed unexpectedly; keeping last known snapshot.', [
                'external_account_id' => $account->getExternalAccountId(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /** @return array{refreshed: int, skipped: int, failed: int} */
    public function pullAllStale(): array
    {
        $refreshed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->tenantAccountRepository->findAllOrdered() as $account) {
            if (!$this->isStale($account)) {
                ++$skipped;
                continue;
            }

            $before = $account->getLastSyncedAt()?->format(\DateTimeInterface::ATOM);
            $this->ensureFresh($account);
            $after = $account->getLastSyncedAt()?->format(\DateTimeInterface::ATOM);

            if ($before !== $after) {
                ++$refreshed;
            } else {
                ++$failed;
            }
        }

        return [
            'refreshed' => $refreshed,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }
}
