<?php

namespace App\Fournisseur\Infrastructure\Persistence\Doctrine;

use App\Fournisseur\Domain\Entity\PaiementFournisseur;
use App\Fournisseur\Domain\Repository\PaiementFournisseurRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<PaiementFournisseur>
 */
class DoctrinePaiementFournisseurRepository extends ServiceEntityRepository implements PaiementFournisseurRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaiementFournisseur::class);
    }

    public function save(PaiementFournisseur $paiement, bool $flush = true): void
    {
        $this->getEntityManager()->persist($paiement);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(Uuid $id): ?PaiementFournisseur
    {
        return $this->find($id);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.paidAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByDetteId(Uuid $detteId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.detteFournisseurId = :detteId')
            ->setParameter('detteId', $detteId, 'uuid')
            ->orderBy('p.paidAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByFournisseurId(Uuid $fournisseurId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere(
                'p.detteFournisseurId IN (
                    SELECT d.id FROM App\Fournisseur\Domain\Entity\DetteFournisseur d WHERE d.fournisseurId = :fournisseurId
                )'
            )
            ->setParameter('fournisseurId', $fournisseurId, 'uuid')
            ->orderBy('p.paidAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
