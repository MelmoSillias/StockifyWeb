<?php

namespace App\Fournisseur\Infrastructure\Persistence\Doctrine;

use App\Fournisseur\Domain\Entity\CommandeFournisseur;
use App\Fournisseur\Domain\Repository\CommandeFournisseurRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<CommandeFournisseur>
 */
class DoctrineCommandeFournisseurRepository extends ServiceEntityRepository implements CommandeFournisseurRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommandeFournisseur::class);
    }

    public function save(CommandeFournisseur $commande, bool $flush = true): void
    {
        $this->getEntityManager()->persist($commande);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(Uuid $id): ?CommandeFournisseur
    {
        return $this->find($id);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByFournisseurId(Uuid $fournisseurId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.fournisseurId = :fournisseurId')
            ->setParameter('fournisseurId', $fournisseurId, 'uuid')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function existsByFournisseurId(Uuid $fournisseurId): bool
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.fournisseurId = :fournisseurId')
            ->setParameter('fournisseurId', $fournisseurId, 'uuid')
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
