<?php

namespace App\Tenancy\Presentation\Api\Serializer;

use App\Tenancy\Domain\Entity\Account;
use App\Tenancy\Domain\Entity\Shop;

final class AccountSerializer
{
    /** @return array<string, mixed> */
    public function serializeAccount(Account $account): array
    {
        return [
            'id' => (string) $account->getId(),
            'name' => $account->getName(),
            'slug' => $account->getSlug(),
            'status' => $account->getStatus()->value,
            'default_currency' => $account->getDefaultCurrency(),
            'timezone' => $account->getTimezone(),
            'shops' => array_map($this->serializeShop(...), $account->getShops()->toArray()),
        ];
    }

    /** @return array<string, mixed> */
    public function serializeShop(Shop $shop): array
    {
        return [
            'id' => (string) $shop->getId(),
            'account_id' => (string) $shop->getAccount()->getId(),
            'name' => $shop->getName(),
            'slug' => $shop->getSlug(),
            'status' => $shop->getStatus()->value,
            'currency' => $shop->getCurrency(),
        ];
    }

    /** @return array<string, mixed> */
    public function serializeAccountSummary(Account $account): array
    {
        return [
            'id' => (string) $account->getId(),
            'name' => $account->getName(),
            'slug' => $account->getSlug(),
            'status' => $account->getStatus()->value,
            'shops_count' => $account->getShops()->count(),
        ];
    }

    /** @return array<string, mixed> */
    public function serializeShopWithAccount(Shop $shop): array
    {
        return [
            'id' => (string) $shop->getId(),
            'account_id' => (string) $shop->getAccount()->getId(),
            'account_name' => $shop->getAccount()->getName(),
            'name' => $shop->getName(),
            'slug' => $shop->getSlug(),
            'status' => $shop->getStatus()->value,
            'currency' => $shop->getCurrency(),
        ];
    }
}
