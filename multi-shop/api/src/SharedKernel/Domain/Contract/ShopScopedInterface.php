<?php

namespace App\SharedKernel\Domain\Contract;

use Symfony\Component\Uid\Uuid;

interface ShopScopedInterface
{
    public function getShopId(): ?Uuid;

    public function setShopId(Uuid $shopId): void;
}
