<?php

namespace App\Shop\Application\Command\AddShopMembership;

use Symfony\Component\Uid\Uuid;

final readonly class AddShopMembershipCommand
{
    public function __construct(
        public Uuid $shopId,
        public Uuid $userId,
    ) {
    }
}
