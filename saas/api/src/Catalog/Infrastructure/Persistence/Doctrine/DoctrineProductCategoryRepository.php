<?php

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Entity\ProductCategory;
use App\Catalog\Domain\Repository\ProductCategoryRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ProductCategory>
 */
class DoctrineProductCategoryRepository extends ServiceEntityRepository implements ProductCategoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductCategory::class);
    }

    public function findByShop(Uuid $shopId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.shopId = :shopId')
            ->setParameter('shopId', $shopId, 'uuid')
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(ProductCategory $category, bool $flush = true): void
    {
        $this->getEntityManager()->persist($category);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProductCategory $category): void
    {
        $em = $this->getEntityManager();
        $em->remove($category);
        $em->flush();
    }

    public function findById(Uuid $id): ?ProductCategory
    {
        return $this->find($id);
    }
}
