<?php

namespace App\Shop\Application\Command\DeleteShop;

use Symfony\Component\Uid\Uuid;

final readonly class DeleteShopCommand
{
    public function __construct(
        public Uuid $shopId,
    ) {
    }
}
