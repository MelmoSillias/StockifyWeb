<?php

namespace App\Fournisseur\Infrastructure\Persistence\Doctrine;

use App\Fournisseur\Domain\Entity\Fournisseur;
use App\Fournisseur\Domain\Repository\FournisseurRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Fournisseur>
 */
class DoctrineFournisseurRepository extends ServiceEntityRepository implements FournisseurRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fournisseur::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.deletedAt IS NULL')
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function remove(Fournisseur $fournisseur, bool $flush = true): void
    {
        $this->getEntityManager()->remove($fournisseur);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function save(Fournisseur $fournisseur, bool $flush = true): void
    {
        $this->getEntityManager()->persist($fournisseur);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(Uuid $id): ?Fournisseur
    {
        return $this->find($id);
    }
}
