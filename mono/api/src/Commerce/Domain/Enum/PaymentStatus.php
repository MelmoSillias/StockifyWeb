<?php

namespace App\Commerce\Domain\Enum;

enum PaymentStatus: string
{
    case Impaye = 'impaye';
    case PartiellementPaye = 'partiellement_paye';
    case Paye = 'paye';
    case Annulee = 'annulee';
}
