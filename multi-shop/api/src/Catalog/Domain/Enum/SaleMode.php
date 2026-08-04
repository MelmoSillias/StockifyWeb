<?php

namespace App\Catalog\Domain\Enum;

enum SaleMode: string
{
    case Unit = 'unit';
    case Weight = 'weight';
    case Volume = 'volume';
    case Bundle = 'bundle';
}
