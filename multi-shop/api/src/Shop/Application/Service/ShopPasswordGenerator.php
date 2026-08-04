<?php

namespace App\Shop\Application\Service;

use App\Shop\Domain\ValueObject\GeneratedPassword;

final class ShopPasswordGenerator
{
    /** @var list<string> */
    private const WORDS = [
        'Boutique', 'Stock', 'Magasin', 'Commerce', 'Vente',
        'Caisse', 'Depot', 'Market', 'Store', 'Shop',
    ];

    public function generate(): GeneratedPassword
    {
        $word = self::WORDS[random_int(0, count(self::WORDS) - 1)];
        $digits = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        return GeneratedPassword::fromPlain($word.$digits.'!');
    }
}
