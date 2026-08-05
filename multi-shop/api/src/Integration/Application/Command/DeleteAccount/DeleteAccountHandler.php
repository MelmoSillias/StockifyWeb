<?php

namespace App\Integration\Application\Command\DeleteAccount;

use App\Integration\Application\Service\TenantPurgeService;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use App\Shop\Domain\Repository\ShopRepositoryInterface;

final readonly class DeleteAccountCommand
{
    public const MODE_GUARD = 'guard';
    public const MODE_PURGE = 'purge';

    public function __construct(
        public string $externalAccountId,
        public string $mode = self::MODE_GUARD,
    ) {
    }
}

final readonly class DeleteAccountResult
{
    /**
     * @param array<string, mixed>|null $deletionReceipt
     */
    public function __construct(
        public int $statusCode,
        public ?array $deletionReceipt = null,
    ) {
    }
}

final class DeleteAccountHandler
{
    public function __construct(
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly TenantPurgeService $tenantPurgeService,
    ) {
    }

    public function handle(DeleteAccountCommand $command): DeleteAccountResult
    {
        $mode = strtolower(trim($command->mode));
        if (!in_array($mode, [DeleteAccountCommand::MODE_GUARD, DeleteAccountCommand::MODE_PURGE], true)) {
            throw new \InvalidArgumentException('Invalid delete mode. Expected guard or purge.');
        }

        if (DeleteAccountCommand::MODE_PURGE === $mode) {
            if (!$this->tenantPurgeService->isPurgeEnabled()) {
                throw new \DomainException('Tenant purge is disabled.');
            }

            $receipt = $this->tenantPurgeService->purge($command->externalAccountId);

            return new DeleteAccountResult(202, $receipt);
        }

        $account = $this->tenantAccountRepository->findByExternalAccountId($command->externalAccountId);
        if (null === $account) {
            throw new \InvalidArgumentException('Tenant account not found.');
        }

        $shops = $this->shopRepository->findByTenantAccountId($account->getId());
        if ([] !== $shops) {
            throw new \DomainException('Cannot delete tenant account with existing shops.');
        }

        $this->tenantAccountRepository->remove($account);

        return new DeleteAccountResult(204);
    }
}
