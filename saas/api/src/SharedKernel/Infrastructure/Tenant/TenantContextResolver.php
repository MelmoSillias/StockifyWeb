<?php

namespace App\SharedKernel\Infrastructure\Tenant;

use App\IdentityAccess\Domain\Entity\User;
use App\SharedKernel\Domain\ValueObject\TenantContext;
use App\Tenancy\Domain\Entity\Account;
use App\Tenancy\Domain\Entity\Shop;
use App\Tenancy\Domain\Enum\AccountMemberRole;
use App\Tenancy\Domain\Enum\ShopMemberRole;
use App\Tenancy\Domain\Repository\AccountMemberRepositoryInterface;
use App\Tenancy\Domain\Repository\AccountRepositoryInterface;
use App\Tenancy\Domain\Repository\ShopMemberRepositoryInterface;
use App\Tenancy\Domain\Repository\ShopRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

final class TenantContextResolver
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly AccountMemberRepositoryInterface $accountMemberRepository,
        private readonly ShopMemberRepositoryInterface $shopMemberRepository,
    ) {
    }

    public function resolve(User $user, Request $request): TenantContext
    {
        $accountId = $this->resolveHeader($request, 'X-Account-Id');
        $shopId = $this->resolveHeader($request, 'X-Shop-Id');

        $account = $this->findAccount($accountId);
        $shop = $this->findShop($shopId, $account);

        $accountMember = $this->accountMemberRepository->findActiveMembership($account, $user);
        if (null === $accountMember) {
            throw new AccessDeniedHttpException('No active account membership.');
        }

        $accountRole = $accountMember->getRole();
        $shopRole = null;

        if (in_array($accountRole, [AccountMemberRole::Owner, AccountMemberRole::Admin], true)) {
            return new TenantContext($user, $account, $shop, $accountRole, ShopMemberRole::Manager);
        }

        $shopMember = $this->shopMemberRepository->findActiveMembership($shop, $user);
        if (null === $shopMember) {
            throw new AccessDeniedHttpException('No active shop membership.');
        }

        $shopRole = $shopMember->getRole();

        return new TenantContext($user, $account, $shop, $accountRole, $shopRole);
    }

    private function resolveHeader(Request $request, string $name): Uuid
    {
        $value = $request->headers->get($name);
        if (null === $value || '' === $value) {
            throw new BadRequestHttpException(sprintf('Missing header %s.', $name));
        }

        if (!Uuid::isValid($value)) {
            throw new BadRequestHttpException(sprintf('Invalid UUID in header %s.', $name));
        }

        return Uuid::fromString($value);
    }

    private function findAccount(Uuid $accountId): Account
    {
        $account = $this->accountRepository->findById($accountId);
        if (null === $account) {
            throw new AccessDeniedHttpException('Account not found.');
        }

        return $account;
    }

    private function findShop(Uuid $shopId, Account $account): Shop
    {
        $shop = $this->shopRepository->findById($shopId);
        if (null === $shop || !$shop->getAccount()->getId()->equals($account->getId())) {
            throw new AccessDeniedHttpException('Shop not found for this account.');
        }

        return $shop;
    }
}
