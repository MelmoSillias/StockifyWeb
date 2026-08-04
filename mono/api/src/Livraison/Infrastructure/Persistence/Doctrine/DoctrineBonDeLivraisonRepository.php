<?php

namespace App\Livraison\Infrastructure\Persistence\Doctrine;

use App\Livraison\Domain\Entity\BonDeLivraison;
use App\Livraison\Domain\Repository\BonDeLivraisonRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<BonDeLivraison>
 */
class DoctrineBonDeLivraisonRepository extends ServiceEntityRepository implements BonDeLivraisonRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonDeLivraison::class);
    }

    public function save(BonDeLivraison $bonDeLivraison, bool $flush = true): void
    {
        $this->getEntityManager()->persist($bonDeLivraison);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(Uuid $id): ?BonDeLivraison
    {
        return $this->find($id);
    }

    public function findByCommandeId(Uuid $commandeId): array
    {
        return $this->createQueryBuilder('bl')
            ->andWhere('bl.commandeId = :commandeId')
            ->setParameter('commandeId', $commandeId, 'uuid')
            ->orderBy('bl.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function sumShippedQuantitiesByCommandeId(Uuid $commandeId): array
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('line.variantId AS variantId', 'SUM(line.quantity) AS totalQuantity')
            ->from(BonDeLivraison::class, 'bl')
            ->innerJoin('bl.lines', 'line')
            ->andWhere('bl.commandeId = :commandeId')
            ->setParameter('commandeId', $commandeId, 'uuid')
            ->groupBy('line.variantId')
            ->getQuery()
            ->getArrayResult();

        $totals = [];
        foreach ($rows as $row) {
            $totals[(string) $row['variantId']] = (string) $row['totalQuantity'];
        }

        return $totals;
    }
}
