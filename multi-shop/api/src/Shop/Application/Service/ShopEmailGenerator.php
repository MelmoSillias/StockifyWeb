<?php

namespace App\Shop\Application\Service;

use App\Shop\Domain\Entity\Shop;
use App\Shop\Domain\ValueObject\ShopEmail;

final class ShopEmailGenerator
{
    public function generate(string $username, Shop $shop): ShopEmail
    {
        return ShopEmail::forShopUser($username, $shop->getSlug());
    }
}
