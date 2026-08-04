<?php

namespace App\Client\Domain\Enum;

enum ClientStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
}
