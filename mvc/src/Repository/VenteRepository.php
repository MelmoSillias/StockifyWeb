<?php

namespace App\Repository;

use App\Entity\Vente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vente::class);
    }

    public function findStatsFiltered(?\DateTime $start, ?\DateTime $end, ?string $client): array
{
    $qb = $this->createQueryBuilder('v');

    if ($start && $end) {
        $qb->andWhere('v.date BETWEEN :start AND :end')
           ->setParameter('start', $start)
           ->setParameter('end', $end);
    }

    if ($client) {
        $qb->andWhere('v.nomClient LIKE :client')
           ->setParameter('client', '%' . $client . '%');
    }

    return $qb->getQuery()->getResult();
}

public function countVentesDuJour(): int
{
    $today = new \DateTimeImmutable('today');
    $qb = $this->createQueryBuilder('v')
        ->select('COUNT(v.id)')
        ->where('v.date >= :start AND v.date < :end')
        ->setParameter('start', $today)
        ->setParameter('end', $today->modify('+1 day'));
    return (int) $qb->getQuery()->getSingleScalarResult();
}

public function getStatsMensuelles(): array
{
    $conn = $this->getEntityManager()->getConnection();
    $sql = "
        SELECT 
            DATE_FORMAT(date, '%Y-%m') AS period,
            DATE_FORMAT(date, '%b') AS mois,
            SUM(total) AS total
        FROM vente
        GROUP BY period
        ORDER BY period ASC
        LIMIT 12
    ";
    return $conn->executeQuery($sql)->fetchAllAssociative();
}




}
