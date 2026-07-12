<?php

namespace App\Tenancy\Domain\Enum;

enum AccountMemberStatus: string
{
    case Active = 'active';
    case Invited = 'invited';
    case Suspended = 'suspended';
}
