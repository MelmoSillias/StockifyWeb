<?php

namespace App\Repository;

use App\Entity\PaiementCreanceFournisseur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PaiementCreanceFournisseurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaiementCreanceFournisseur::class);
    }

    // Add custom query methods here
}
