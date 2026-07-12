<?php

namespace App\Tenancy\Application\Service;

use App\IdentityAccess\Domain\Entity\User;
use App\Tenancy\Domain\Entity\Account;
use App\Tenancy\Domain\Repository\AccountMemberRepositoryInterface;
use App\Tenancy\Domain\Repository\AccountRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class AccountAuthorizationService
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly AccountMemberRepositoryInterface $accountMemberRepository,
    ) {
    }

    public function getAuthorizedAccount(string $id, User $user): Account
    {
        if (!Uuid::isValid($id)) {
            throw new \DomainException('Account not found');
        }

        $account = $this->accountRepository->findById(Uuid::fromString($id));
        if (null === $account) {
            throw new \DomainException('Account not found');
        }

        if (null === $this->accountMemberRepository->findActiveMembership($account, $user)) {
            throw new \DomainException('Access denied');
        }

        return $account;
    }
}
