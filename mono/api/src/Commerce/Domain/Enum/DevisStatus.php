<?php

namespace App\Commerce\Domain\Enum;

enum DevisStatus: string
{
    case Actif = 'actif';
    case ConvertiVente = 'converti_vente';
    case ConvertiCommande = 'converti_commande';
    case Annule = 'annule';
    case Expire = 'expire';
}
