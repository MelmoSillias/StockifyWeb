<?php

namespace App\Finance\Infrastructure\Persistence\Doctrine;

use App\Finance\Domain\Entity\Compte;
use App\Finance\Domain\Repository\CompteRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Compte>
 */
class DoctrineCompteRepository extends ServiceEntityRepository implements CompteRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Compte::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findById(Uuid $id): ?Compte
    {
        return $this->find($id);
    }

    public function findDefault(): ?Compte
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isDefault = true')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Compte $compte, bool $flush = true): void
    {
        $this->getEntityManager()->persist($compte);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
