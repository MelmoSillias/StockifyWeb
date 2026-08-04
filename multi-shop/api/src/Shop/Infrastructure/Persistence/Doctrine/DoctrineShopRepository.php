<?php

namespace App\Shop\Infrastructure\Persistence\Doctrine;

use App\Shop\Domain\Entity\Shop;
use App\Shop\Domain\Repository\ShopRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Shop>
 */
class DoctrineShopRepository extends ServiceEntityRepository implements ShopRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shop::class);
    }

    public function findById(Uuid $id): ?Shop
    {
        return $this->find($id);
    }

    public function findBySlug(string $slug): ?Shop
    {
        return $this->findOneBy(['slug' => strtolower(trim($slug))]);
    }

    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByTenantAccountId(Uuid $tenantAccountId): array
    {
        return $this->findBy(['tenantAccountId' => $tenantAccountId], ['name' => 'ASC']);
    }

    public function save(Shop $shop, bool $flush = true): void
    {
        $this->getEntityManager()->persist($shop);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Shop $shop, bool $flush = true): void
    {
        $this->getEntityManager()->remove($shop);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
