<?php

namespace App\Platform\Application\Query\GetPlatformStats;

use App\Tenancy\Domain\Repository\AccountRepositoryInterface;
use App\Tenancy\Domain\Repository\ShopRepositoryInterface;

final class GetPlatformStatsHandler
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly ShopRepositoryInterface $shopRepository,
    ) {
    }

    /** @return array{accounts_count: int, shops_count: int} */
    public function handle(GetPlatformStatsQuery $query): array
    {
        return [
            'accounts_count' => $this->accountRepository->countAll(),
            'shops_count' => $this->shopRepository->countAll(),
        ];
    }
}
