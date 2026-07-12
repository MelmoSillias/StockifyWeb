<?php

namespace App\SharedKernel\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

final readonly class TenantScope
{
    public function __construct(
        private Uuid $accountId,
        private Uuid $shopId,
    ) {
    }

    public function accountId(): Uuid
    {
        return $this->accountId;
    }

    public function shopId(): Uuid
    {
        return $this->shopId;
    }
}
