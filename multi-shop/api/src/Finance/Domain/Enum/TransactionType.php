<?php

namespace App\Finance\Domain\Enum;

enum TransactionType: string
{
    case Revenu = 'revenu';
    case Depense = 'depense';
}
