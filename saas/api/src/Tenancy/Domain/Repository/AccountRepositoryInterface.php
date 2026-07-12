<?php

namespace App\Tenancy\Domain\Repository;

use App\Tenancy\Domain\Entity\Account;
use Symfony\Component\Uid\Uuid;

interface AccountRepositoryInterface
{
    public function save(Account $account, bool $flush = true): void;

    public function findById(Uuid $id): ?Account;

    public function countAll(): int;

    /** @return list<Account> */
    public function findAllOrderedByName(): array;
}
