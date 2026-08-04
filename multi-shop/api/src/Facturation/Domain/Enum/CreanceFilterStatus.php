<?php

namespace App\Facturation\Domain\Enum;

enum CreanceFilterStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case All = 'all';
}
