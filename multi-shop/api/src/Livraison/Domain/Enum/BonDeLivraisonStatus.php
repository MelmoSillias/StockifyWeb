<?php

namespace App\Livraison\Domain\Enum;

enum BonDeLivraisonStatus: string
{
    case Envoye = 'envoye';
    case Delivre = 'delivre';
}
