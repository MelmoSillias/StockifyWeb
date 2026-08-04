<?php

namespace App\Commerce\Domain\Enum;

enum CommandeStatus: string
{
    case Initiee = 'initiee';
    case Confirmee = 'confirmee';
    case PartiellementLivree = 'partiellement_livree';
    case Livree = 'livree';
    case Annulee = 'annulee';
}
