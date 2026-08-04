<?php

namespace App\Inventory\Domain\Enum;

enum MovementType: string
{
    case Purchase = 'purchase';
    case Adjustment = 'adjustment';
    case Transfer = 'transfer';
    case Sale = 'sale';
}
