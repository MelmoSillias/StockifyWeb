<?php

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entity\StockMovement;
use App\Inventory\Domain\Repository\StockMovementRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<StockMovement>
 */
class DoctrineStockMovementRepository extends ServiceEntityRepository implements StockMovementRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockMovement::class);
    }

    public function findAll(?Uuid $variantId = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->addSelect('v', 'p', 'c', 'u', 'a', 'l')
            ->innerJoin('m.variant', 'v')
            ->innerJoin('v.product', 'p')
            ->leftJoin('p.category', 'c')
            ->innerJoin('v.unitOfMeasure', 'u')
            ->leftJoin('m.allocations', 'a')
            ->leftJoin('a.lot', 'l')
            ->orderBy('m.occurredAt', 'DESC');

        if (null !== $variantId) {
            $qb->andWhere('v.id = :variantId')
                ->setParameter('variantId', $variantId, 'uuid');
        }

        return $qb->getQuery()->getResult();
    }

    public function save(StockMovement $movement, bool $flush = true): void
    {
        $this->getEntityManager()->persist($movement);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
