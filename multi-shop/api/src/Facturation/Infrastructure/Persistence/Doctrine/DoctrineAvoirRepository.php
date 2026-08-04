<?php

namespace App\Facturation\Infrastructure\Persistence\Doctrine;

use App\Facturation\Domain\Entity\Avoir;
use App\Facturation\Domain\Repository\AvoirRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Avoir>
 */
class DoctrineAvoirRepository extends ServiceEntityRepository implements AvoirRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avoir::class);
    }

    public function save(Avoir $avoir, bool $flush = true): void
    {
        $this->getEntityManager()->persist($avoir);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(Uuid $id): ?Avoir
    {
        return $this->find($id);
    }

    public function findByVenteId(Uuid $venteId): ?Avoir
    {
        return $this->findOneBy(['venteId' => $venteId]);
    }
}
