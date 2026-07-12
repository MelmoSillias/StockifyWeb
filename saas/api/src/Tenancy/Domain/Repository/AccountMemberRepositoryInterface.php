<?php

namespace App\Tenancy\Domain\Repository;

use App\IdentityAccess\Domain\Entity\User;
use App\Tenancy\Domain\Entity\Account;
use App\Tenancy\Domain\Entity\AccountMember;

interface AccountMemberRepositoryInterface
{
    /** @return list<AccountMember> */
    public function findActiveByUser(User $user): array;

    public function findActiveMembership(Account $account, User $user): ?AccountMember;

    public function save(AccountMember $member, bool $flush = true): void;
}
