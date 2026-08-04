<?php

namespace App\Catalog\Domain\Enum;

enum VariantStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
