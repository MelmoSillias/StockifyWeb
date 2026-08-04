<?php

namespace App\Commerce\Infrastructure\Persistence\Doctrine;

use App\Commerce\Domain\Entity\Commande;
use App\Commerce\Domain\Enum\CommandeStatus;
use App\Commerce\Domain\Repository\CommandeRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class DoctrineCommandeRepository extends ServiceEntityRepository implements CommandeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Commande $commande, bool $flush = true): void
    {
        $this->getEntityManager()->persist($commande);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(Uuid $id): ?Commande
    {
        return $this->find($id);
    }

    public function findByClientId(Uuid $clientId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.clientId = :clientId')
            ->setParameter('clientId', $clientId, 'uuid')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveForStockReservation(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.status IN (:statuses)')
            ->setParameter('statuses', [CommandeStatus::Confirmee, CommandeStatus::PartiellementLivree])
            ->getQuery()
            ->getResult();
    }

    public function existsByClientId(Uuid $clientId): bool
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.clientId = :clientId')
            ->setParameter('clientId', $clientId, 'uuid')
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
