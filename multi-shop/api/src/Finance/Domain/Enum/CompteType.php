<?php

namespace App\Finance\Domain\Enum;

enum CompteType: string
{
    case Caisse = 'caisse';
    case Banque = 'banque';
}
