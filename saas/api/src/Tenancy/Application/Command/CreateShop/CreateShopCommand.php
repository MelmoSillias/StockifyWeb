<?php

namespace App\Tenancy\Application\Command\CreateShop;

final readonly class CreateShopCommand
{
    public function __construct(
        public string $accountId,
        public string $name,
        public string $slug,
    ) {
    }
}
