<?php

namespace App\Fournisseur\Domain\Enum;

enum CommandeFournisseurStatus: string
{
    case Initiee = 'initiee';
    case Confirmee = 'confirmee';
    case Recue = 'recue';
    case Annulee = 'annulee';
}
