<?php

namespace App\Commerce\Infrastructure\Persistence\Doctrine;

use App\Commerce\Domain\Entity\Vente;
use App\Commerce\Domain\Repository\VenteRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Vente>
 */
class DoctrineVenteRepository extends ServiceEntityRepository implements VenteRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vente::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('v')
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Vente $vente, bool $flush = true): void
    {
        $this->getEntityManager()->persist($vente);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(Uuid $id): ?Vente
    {
        return $this->find($id);
    }

    public function findByClientId(Uuid $clientId): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.clientId = :clientId')
            ->setParameter('clientId', $clientId, 'uuid')
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function existsByClientId(Uuid $clientId): bool
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.clientId = :clientId')
            ->setParameter('clientId', $clientId, 'uuid')
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
