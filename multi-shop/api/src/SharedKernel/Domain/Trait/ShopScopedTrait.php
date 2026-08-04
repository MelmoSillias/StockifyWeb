<?php

namespace App\SharedKernel\Domain\Trait;

use App\SharedKernel\Domain\Contract\ShopScopedInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

trait ShopScopedTrait
{
    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $shopId = null;

    public function setShopId(Uuid $shopId): void
    {
        $this->shopId = $shopId;
    }

    public function getShopId(): ?Uuid
    {
        return $this->shopId;
    }
}
