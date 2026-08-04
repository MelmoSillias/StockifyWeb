<?php

namespace App\Fournisseur\Infrastructure\Persistence\Doctrine;

use App\Fournisseur\Domain\Entity\DetteFournisseur;
use App\Fournisseur\Domain\Enum\DetteFilterStatus;
use App\Fournisseur\Domain\Repository\DetteFournisseurRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<DetteFournisseur>
 */
class DoctrineDetteFournisseurRepository extends ServiceEntityRepository implements DetteFournisseurRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DetteFournisseur::class);
    }

    public function save(DetteFournisseur $dette, bool $flush = true): void
    {
        $this->getEntityManager()->persist($dette);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(Uuid $id): ?DetteFournisseur
    {
        return $this->find($id);
    }

    public function findDettes(?Uuid $fournisseurId, DetteFilterStatus $status): array
    {
        $qb = $this->createQueryBuilder('d')
            ->orderBy('d.issuedAt', 'DESC');

        if (null !== $fournisseurId) {
            $qb->andWhere('d.fournisseurId = :fournisseurId')
                ->setParameter('fournisseurId', $fournisseurId, 'uuid');
        }

        if (DetteFilterStatus::Open === $status) {
            $qb->andWhere('d.creditClosedAt IS NULL');
        } elseif (DetteFilterStatus::Closed === $status) {
            $qb->andWhere('d.creditClosedAt IS NOT NULL');
        }

        return $qb->getQuery()->getResult();
    }

    public function existsByFournisseurId(Uuid $fournisseurId): bool
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.fournisseurId = :fournisseurId')
            ->setParameter('fournisseurId', $fournisseurId, 'uuid')
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
