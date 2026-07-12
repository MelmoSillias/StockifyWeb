<?php

namespace App\SharedKernel\Domain\ValueObject;

use App\IdentityAccess\Domain\Entity\User;
use App\Tenancy\Domain\Entity\Account;
use App\Tenancy\Domain\Entity\Shop;
use App\Tenancy\Domain\Enum\AccountMemberRole;
use App\Tenancy\Domain\Enum\ShopMemberRole;

final class TenantContext
{
    public function __construct(
        private readonly User $user,
        private readonly Account $account,
        private readonly Shop $shop,
        private readonly ?AccountMemberRole $accountRole,
        private readonly ?ShopMemberRole $shopRole,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getShop(): Shop
    {
        return $this->shop;
    }

    public function getAccountRole(): ?AccountMemberRole
    {
        return $this->accountRole;
    }

    public function getShopRole(): ?ShopMemberRole
    {
        return $this->shopRole;
    }

    public function isAccountAdmin(): bool
    {
        return in_array($this->accountRole, [AccountMemberRole::Owner, AccountMemberRole::Admin], true);
    }

    public function canManageCatalog(): bool
    {
        if ($this->isAccountAdmin()) {
            return true;
        }

        return in_array($this->shopRole, [ShopMemberRole::Manager], true);
    }

    public function canManageStock(): bool
    {
        if ($this->isAccountAdmin()) {
            return true;
        }

        return in_array($this->shopRole, [ShopMemberRole::Manager, ShopMemberRole::Cashier], true);
    }

    public function canView(): bool
    {
        if ($this->isAccountAdmin()) {
            return true;
        }

        return null !== $this->shopRole;
    }
}
