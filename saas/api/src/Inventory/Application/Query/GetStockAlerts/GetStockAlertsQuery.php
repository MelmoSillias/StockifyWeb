<?php

namespace App\Inventory\Application\Query\GetStockAlerts;

use App\SharedKernel\Infrastructure\Tenant\TenantContextHolder;
use Symfony\Component\Uid\Uuid;

final readonly class GetStockAlertsQuery
{
    public function __construct(
        public Uuid $shopId,
    ) {
    }

    public static function forCurrentShop(TenantContextHolder $holder): self
    {
        return new self($holder->get()->getShop()->getId());
    }
}
