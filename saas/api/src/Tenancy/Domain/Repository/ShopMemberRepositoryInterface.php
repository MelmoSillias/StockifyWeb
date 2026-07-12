<?php

namespace App\Tenancy\Domain\Repository;

use App\IdentityAccess\Domain\Entity\User;
use App\Tenancy\Domain\Entity\Shop;
use App\Tenancy\Domain\Entity\ShopMember;

interface ShopMemberRepositoryInterface
{
    public function findActiveMembership(Shop $shop, User $user): ?ShopMember;

    public function save(ShopMember $member, bool $flush = true): void;
}
