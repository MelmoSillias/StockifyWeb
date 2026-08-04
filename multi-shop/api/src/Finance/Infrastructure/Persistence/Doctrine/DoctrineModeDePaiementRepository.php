<?php

namespace App\Finance\Infrastructure\Persistence\Doctrine;

use App\Finance\Domain\Entity\ModeDePaiement;
use App\Finance\Domain\Repository\ModeDePaiementRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ModeDePaiement>
 */
class DoctrineModeDePaiementRepository extends ServiceEntityRepository implements ModeDePaiementRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModeDePaiement::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.isActive = true')
            ->orderBy('m.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findById(Uuid $id): ?ModeDePaiement
    {
        return $this->find($id);
    }

    public function findByCode(string $code): ?ModeDePaiement
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.code = :code')
            ->setParameter('code', $code)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(ModeDePaiement $mode, bool $flush = true): void
    {
        $this->getEntityManager()->persist($mode);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ModeDePaiement $mode, bool $flush = true): void
    {
        $this->getEntityManager()->remove($mode);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
