<?php

namespace App\Tenancy\Domain\Enum;

enum ShopMemberStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
}
