<?php

namespace App\Tenancy\Domain\Enum;

enum ShopStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
