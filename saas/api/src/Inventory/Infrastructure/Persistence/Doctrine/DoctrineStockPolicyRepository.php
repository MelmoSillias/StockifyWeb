<?php

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Entity\ProductVariant;
use App\Inventory\Domain\Entity\StockPolicy;
use App\Inventory\Domain\Repository\StockPolicyRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockPolicy>
 */
class DoctrineStockPolicyRepository extends ServiceEntityRepository implements StockPolicyRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockPolicy::class);
    }

    public function findByVariant(ProductVariant $variant): ?StockPolicy
    {
        return $this->findOneBy(['variant' => $variant]);
    }

    public function save(StockPolicy $policy, bool $flush = true): void
    {
        $this->getEntityManager()->persist($policy);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
