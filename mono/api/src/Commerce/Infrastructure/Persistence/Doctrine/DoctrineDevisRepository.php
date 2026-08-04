<?php

namespace App\Commerce\Infrastructure\Persistence\Doctrine;

use App\Commerce\Domain\Entity\Devis;
use App\Commerce\Domain\Repository\DevisRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Devis>
 */
class DoctrineDevisRepository extends ServiceEntityRepository implements DevisRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Devis::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Devis $devis, bool $flush = true): void
    {
        $this->getEntityManager()->persist($devis);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(Uuid $id): ?Devis
    {
        return $this->find($id);
    }
}
