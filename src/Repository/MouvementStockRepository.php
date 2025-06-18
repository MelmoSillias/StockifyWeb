<?php

namespace App\Repository;

use App\Entity\MouvementStock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MouvementStockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MouvementStock::class);
    }

    public function findFiltered(array $filters): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.produit', 'p')
            ->addSelect('p')
            ->orderBy('m.date', 'DESC');
    
        if (!empty($filters['produit'])) {
            $qb->andWhere('p.nom LIKE :produit OR p.id = :produit_id')
               ->setParameter('produit', '%' . $filters['produit'] . '%')
               ->setParameter('produit_id', (int)$filters['produit']);
        }
    
        if (!empty($filters['type'])) {
            $qb->andWhere('m.type = :type')
               ->setParameter('type', $filters['type']);
        }
    
        if (!empty($filters['date_start'])) {
            $qb->andWhere('m.date >= :dateStart')
               ->setParameter('dateStart', new \DateTime($filters['date_start']));
        }
    
        if (!empty($filters['date_end'])) {
            $qb->andWhere('m.date <= :dateEnd')
               ->setParameter('dateEnd', new \DateTime($filters['date_end'] . ' 23:59:59'));
        }
    
        return $qb->getQuery()->getResult();
    }

    public function countByType(string $type, array $filters = []): int
{
    $qb = $this->createQueryBuilder('m')
        ->select('COUNT(m.id)')
        ->leftJoin('m.produit', 'p')
        ->where('m.type = :type')
        ->setParameter('type', $type);

    if (!empty($filters['produit'])) {
        $qb->andWhere('p.nom LIKE :produit OR p.id = :produit_id')
           ->setParameter('produit', '%' . $filters['produit'] . '%')
           ->setParameter('produit_id', (int)$filters['produit']);
    }

    if (!empty($filters['date_start'])) {
        $qb->andWhere('m.date >= :dateStart')
           ->setParameter('dateStart', new \DateTime($filters['date_start']));
    }

    if (!empty($filters['date_end'])) {
        $qb->andWhere('m.date <= :dateEnd')
           ->setParameter('dateEnd', new \DateTime($filters['date_end'] . ' 23:59:59'));
    }

    return (int) $qb->getQuery()->getSingleScalarResult();
}

    
}
