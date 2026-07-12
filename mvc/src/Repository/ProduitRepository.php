<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    // Add custom query methods here

    public function findProduitsStockFaible(): array
{
    return $this->createQueryBuilder('p')
        ->where('p.seuil_alerte IS NOT NULL AND p.stock_actuel <= p.seuil_alerte')
        ->getQuery()
        ->getResult();
}

public function getRepartitionParCategorie(): array
{
    return $this->createQueryBuilder('p')
        ->select('p.categorie AS categorie, COUNT(p.id) AS count')
        ->groupBy('p.categorie')
        ->getQuery()
        ->getResult();
}

}
