<?php

namespace App\Tenancy\Domain\Enum;

enum AccountStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Suspended = 'suspended';
    case Closed = 'closed';
}
