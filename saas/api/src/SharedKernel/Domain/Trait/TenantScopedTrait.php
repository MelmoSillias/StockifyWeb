<?php

namespace App\SharedKernel\Domain\Trait;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

trait TenantScopedTrait
{
    #[ORM\Column(type: 'uuid')]
    private Uuid $accountId;

    #[ORM\Column(type: 'uuid')]
    private Uuid $shopId;

    public function setTenantScope(Uuid $accountId, Uuid $shopId): void
    {
        $this->accountId = $accountId;
        $this->shopId = $shopId;
    }

    public function getAccountId(): Uuid
    {
        return $this->accountId;
    }

    public function getShopId(): Uuid
    {
        return $this->shopId;
    }
}
