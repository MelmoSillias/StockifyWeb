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

    public function findAllWithCatalogRelations(): array
    {
        return $this->createQueryBuilder('v')
            ->addSelect('p', 'c', 'u')
            ->innerJoin('v.product', 'p')
            ->leftJoin('p.category', 'c')
            ->innerJoin('v.unitOfMeasure', 'u')
            ->orderBy('p.name', 'ASC')
            ->addOrderBy('v.sku', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findById(Uuid $id): ?ProductVariant
    {
        return $this->find($id);
    }

    public function findWithAlertThreshold(): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.alertThreshold IS NOT NULL')
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
