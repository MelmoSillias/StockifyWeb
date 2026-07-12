<?php

namespace App\Repository;

use App\Entity\CreanceFournisseur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CreanceFournisseurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreanceFournisseur::class);
    }

    // Add custom query methods here
    public function countEnCours(): int
{
    return $this->createQueryBuilder('c')
        ->select('COUNT(c.id)')
        ->where('c.montant_restant > 0')
        ->getQuery()->getSingleScalarResult();
}

}
