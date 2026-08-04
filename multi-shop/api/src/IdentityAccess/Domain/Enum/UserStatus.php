<?php

namespace App\IdentityAccess\Domain\Enum;

enum UserStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
}
