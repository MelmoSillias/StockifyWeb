<?php

namespace App\Integration\Domain\Enum;

enum TenantAccountStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Provisioning = 'provisioning';
}
