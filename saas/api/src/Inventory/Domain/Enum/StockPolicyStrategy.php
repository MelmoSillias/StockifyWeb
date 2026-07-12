<?php

namespace App\Inventory\Domain\Enum;

enum StockPolicyStrategy: string
{
    case Fifo = 'fifo';
    case Lifo = 'lifo';
    case Fefo = 'fefo';
    case Manual = 'manual';
}
