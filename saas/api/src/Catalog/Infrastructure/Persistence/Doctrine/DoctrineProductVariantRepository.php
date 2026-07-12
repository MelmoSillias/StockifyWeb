<?php

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Entity\ProductVariant;
use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ProductVariant>
 */
class DoctrineProductVariantRepository extends ServiceEntityRepository implements ProductVariantRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductVariant::class);
    }

    public function findByProduct(Product $product): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.product = :product')
            ->setParameter('product', $product)
            ->orderBy('v.sku', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findForShop(Uuid $shopId, Uuid $variantId): ?ProductVariant
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.id = :id')
            ->andWhere('v.shopId = :shopId')
            ->setParameter('id', $variantId, 'uuid')
            ->setParameter('shopId', $shopId, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findWithAlertThresholdByShop(Uuid $shopId): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.shopId = :shopId')
            ->andWhere('v.alertThreshold IS NOT NULL')
            ->setParameter('shopId', $shopId, 'uuid')
            ->getQuery()
            ->getResult();
    }

    public function save(ProductVariant $variant, bool $flush = true): void
    {
        $this->getEntityManager()->persist($variant);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProductVariant $variant): void
    {
        $em = $this->getEntityManager();
        $em->remove($variant);
        $em->flush();
    }
}
