<?php

namespace App\Inventory\Domain\Enum;

enum MovementDirection: string
{
    case In = 'in';
    case Out = 'out';
}
