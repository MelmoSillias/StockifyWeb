<?php

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Product>
 */
class DoctrineProductRepository extends ServiceEntityRepository implements ProductRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findByShop(Uuid $shopId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.shopId = :shopId')
            ->setParameter('shopId', $shopId, 'uuid')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Product $product, bool $flush = true): void
    {
        $this->getEntityManager()->persist($product);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Product $product): void
    {
        $em = $this->getEntityManager();
        $em->remove($product);
        $em->flush();
    }

    public function findById(Uuid $id): ?Product
    {
        return $this->find($id);
    }
}
