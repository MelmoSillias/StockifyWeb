<?php

namespace App\SharedKernel\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

final readonly class ShopContext
{
    public function __construct(
        private Uuid $shopId,
        private string $shopName,
        private string $shopSlug,
    ) {
    }

    public function getShopId(): Uuid
    {
        return $this->shopId;
    }

    public function getShopName(): string
    {
        return $this->shopName;
    }

    public function getShopSlug(): string
    {
        return $this->shopSlug;
    }
}
