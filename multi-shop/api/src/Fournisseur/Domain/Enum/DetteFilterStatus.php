<?php

namespace App\Fournisseur\Domain\Enum;

enum DetteFilterStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case All = 'all';
}
