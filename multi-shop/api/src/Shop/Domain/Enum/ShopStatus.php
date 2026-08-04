<?php

namespace App\Shop\Domain\Enum;

enum ShopStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
