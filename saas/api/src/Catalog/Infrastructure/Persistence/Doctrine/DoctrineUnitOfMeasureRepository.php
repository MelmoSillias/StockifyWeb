<?php

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Entity\UnitOfMeasure;
use App\Catalog\Domain\Repository\UnitOfMeasureRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<UnitOfMeasure>
 */
class DoctrineUnitOfMeasureRepository extends ServiceEntityRepository implements UnitOfMeasureRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UnitOfMeasure::class);
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(UnitOfMeasure $unit, bool $flush = true): void
    {
        $this->getEntityManager()->persist($unit);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(Uuid $id): ?UnitOfMeasure
    {
        return $this->find($id);
    }

    public function findByCode(string $code): ?UnitOfMeasure
    {
        return $this->findOneBy(['code' => $code]);
    }
}
