<?php

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entity\StockLotAllocation;
use App\Inventory\Domain\Repository\StockLotAllocationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockLotAllocation>
 */
class DoctrineStockLotAllocationRepository extends ServiceEntityRepository implements StockLotAllocationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockLotAllocation::class);
    }

    public function save(StockLotAllocation $allocation, bool $flush = true): void
    {
        $this->getEntityManager()->persist($allocation);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
