<?php

namespace App\Finance\Domain\Enum;

enum TransactionSourceType: string
{
    case Paiement = 'paiement';
    case PaiementFournisseur = 'paiement_fournisseur';
    case Manuel = 'manuel';
}
