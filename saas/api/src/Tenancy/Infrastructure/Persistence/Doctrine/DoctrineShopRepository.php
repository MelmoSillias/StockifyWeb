<?php

namespace App\Tenancy\Infrastructure\Persistence\Doctrine;

use App\Tenancy\Domain\Entity\Shop;
use App\Tenancy\Domain\Repository\ShopRepositoryInterface;
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

    public function save(Shop $shop, bool $flush = true): void
    {
        $this->getEntityManager()->persist($shop);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(Uuid $id): ?Shop
    {
        return $this->find($id);
    }

    public function countAll(): int
    {
        return $this->count([]);
    }

    public function findAllOrderedByName(): array
    {
        return $this->findBy([], ['name' => 'ASC']);
    }
}
