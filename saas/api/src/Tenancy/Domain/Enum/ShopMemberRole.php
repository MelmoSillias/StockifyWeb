<?php

namespace App\Tenancy\Domain\Enum;

enum ShopMemberRole: string
{
    case Manager = 'manager';
    case Cashier = 'cashier';
    case Viewer = 'viewer';
}
