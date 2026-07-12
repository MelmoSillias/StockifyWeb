<?php

namespace App\Platform\Application\Query\ListAllAccounts;

use App\Tenancy\Domain\Entity\Account;
use App\Tenancy\Domain\Repository\AccountRepositoryInterface;

final class ListAllAccountsHandler
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
    ) {
    }

    /** @return list<Account> */
    public function handle(ListAllAccountsQuery $query): array
    {
        return $this->accountRepository->findAllOrderedByName();
    }
}
