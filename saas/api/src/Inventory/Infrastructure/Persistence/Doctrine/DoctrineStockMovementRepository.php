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

    public function findByShop(Uuid $shopId, ?Uuid $variantId = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.shopId = :shopId')
            ->setParameter('shopId', $shopId, 'uuid')
            ->orderBy('m.occurredAt', 'DESC');

        if (null !== $variantId) {
            $qb->andWhere('m.variant = :variantId')
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
