<?php

namespace App\Tenancy\Domain\Enum;

enum AccountMemberRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
}
