<?php

namespace App\Shop\Application\Command\RemoveShopMembership;

use Symfony\Component\Uid\Uuid;

final readonly class RemoveShopMembershipCommand
{
    public function __construct(
        public Uuid $shopId,
        public Uuid $userId,
    ) {
    }
}
