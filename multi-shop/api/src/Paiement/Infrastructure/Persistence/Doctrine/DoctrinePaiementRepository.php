<?php

namespace App\Paiement\Infrastructure\Persistence\Doctrine;

use App\Paiement\Domain\Entity\Paiement;
use App\Paiement\Domain\Repository\PaiementRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Paiement>
 */
class DoctrinePaiementRepository extends ServiceEntityRepository implements PaiementRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paiement::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.paidAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Paiement $paiement, bool $flush = true): void
    {
        $this->getEntityManager()->persist($paiement);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(Uuid $id): ?Paiement
    {
        return $this->find($id);
    }

    public function findByFactureId(Uuid $factureId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.factureId = :factureId')
            ->setParameter('factureId', $factureId, 'uuid')
            ->orderBy('p.paidAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCommandeId(Uuid $commandeId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.commandeId = :commandeId')
            ->setParameter('commandeId', $commandeId, 'uuid')
            ->orderBy('p.paidAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByClientId(Uuid $clientId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere(
                'p.factureId IN (
                    SELECT f.id FROM App\Facturation\Domain\Entity\Facture f WHERE f.clientId = :clientId
                ) OR p.commandeId IN (
                    SELECT c.id FROM App\Commerce\Domain\Entity\Commande c WHERE c.clientId = :clientId
                )'
            )
            ->setParameter('clientId', $clientId, 'uuid')
            ->orderBy('p.paidAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
