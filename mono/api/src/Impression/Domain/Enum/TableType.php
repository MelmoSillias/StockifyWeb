<?php

namespace App\Impression\Domain\Enum;

enum TableType: string
{
    case Ventes = 'ventes';
    case Commandes = 'commandes';
    case Paiements = 'paiements';
    case Clients = 'clients';
    case Products = 'products';
    case Transactions = 'transactions';
    case Movements = 'movements';
    case Creances = 'creances';
    case Dettes = 'dettes';
    case Factures = 'factures';
    case Users = 'users';
}
