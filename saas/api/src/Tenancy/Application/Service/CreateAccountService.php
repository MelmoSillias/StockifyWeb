<?php

namespace App\Tenancy\Application\Service;

use App\IdentityAccess\Domain\Entity\User;
use App\Tenancy\Domain\Entity\Account;
use App\Tenancy\Domain\Entity\AccountMember;
use App\Tenancy\Domain\Entity\Shop;
use App\Tenancy\Domain\Enum\AccountMemberRole;
use App\Tenancy\Domain\Repository\AccountMemberRepositoryInterface;
use App\Tenancy\Domain\Repository\AccountRepositoryInterface;
use App\Tenancy\Domain\Repository\ShopRepositoryInterface;

final class CreateAccountService
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly AccountMemberRepositoryInterface $accountMemberRepository,
        private readonly ShopRepositoryInterface $shopRepository,
    ) {
    }

    public function create(User $owner, string $name, string $slug, string $shopName, string $shopSlug): Account
    {
        $account = new Account($name, $slug);
        $shop = new Shop($account, $shopName, $shopSlug);
        $member = new AccountMember($account, $owner, AccountMemberRole::Owner);

        $this->accountRepository->save($account, false);
        $this->shopRepository->save($shop, false);
        $this->accountMemberRepository->save($member);

        return $account;
    }
}
