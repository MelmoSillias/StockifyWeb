<?php

namespace App\Catalog\Domain\Enum;

enum ProductStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
