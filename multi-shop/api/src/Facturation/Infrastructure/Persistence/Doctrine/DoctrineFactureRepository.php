<?php

namespace App\Facturation\Infrastructure\Persistence\Doctrine;

use App\Facturation\Domain\Entity\Facture;
use App\Facturation\Domain\Enum\CreanceFilterStatus;
use App\Facturation\Domain\Repository\FactureRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Facture>
 */
class DoctrineFactureRepository extends ServiceEntityRepository implements FactureRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Facture::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.issuedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Facture $facture, bool $flush = true): void
    {
        $this->getEntityManager()->persist($facture);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(Uuid $id): ?Facture
    {
        return $this->find($id);
    }

    public function findByVenteId(Uuid $venteId): ?Facture
    {
        return $this->findOneBy(['venteId' => $venteId]);
    }

    public function findByCommandeId(Uuid $commandeId): ?Facture
    {
        return $this->findOneBy(['commandeId' => $commandeId]);
    }

    public function findByClientId(Uuid $clientId): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.clientId = :clientId')
            ->setParameter('clientId', $clientId, 'uuid')
            ->orderBy('f.issuedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findCreances(?Uuid $clientId, CreanceFilterStatus $status): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.isCreance = :isCreance')
            ->setParameter('isCreance', true)
            ->andWhere('f.clientId IS NOT NULL')
            ->orderBy('f.issuedAt', 'DESC');

        if (null !== $clientId) {
            $qb->andWhere('f.clientId = :clientId')
                ->setParameter('clientId', $clientId, 'uuid');
        }

        if (CreanceFilterStatus::Closed === $status) {
            $qb->andWhere('f.creditClosedAt IS NOT NULL');
        } elseif (CreanceFilterStatus::Open === $status) {
            $qb->andWhere('f.creditClosedAt IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    public function existsByClientId(Uuid $clientId): bool
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.clientId = :clientId')
            ->setParameter('clientId', $clientId, 'uuid')
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
