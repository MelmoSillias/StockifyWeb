<?php

namespace App\Catalog\Domain\Enum;

enum CategoryStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
