<?php

namespace App\Repository;

use App\Entity\TransactionCaisse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TransactionCaisseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransactionCaisse::class);
    }

    // Add custom query methods here

    public function calculerSolde(): float
{
    $entries = $this->createQueryBuilder('t')
        ->select("SUM(CASE WHEN t.type = 'entrée' THEN t.montant ELSE -t.montant END)")
        ->getQuery()->getSingleScalarResult();
    return round((float) $entries, 0);
}

}
