<?php

namespace App\Integration\Infrastructure\Persistence\Doctrine;

use App\Integration\Domain\Entity\TenantAccount;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<TenantAccount>
 */
class DoctrineTenantAccountRepository extends ServiceEntityRepository implements TenantAccountRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TenantAccount::class);
    }

    public function findById(Uuid $id): ?TenantAccount
    {
        return $this->find($id);
    }

    public function findByExternalAccountId(string $externalAccountId): ?TenantAccount
    {
        return $this->findOneBy(['externalAccountId' => trim($externalAccountId)]);
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(TenantAccount $account, bool $flush = true): void
    {
        $this->getEntityManager()->persist($account);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TenantAccount $account, bool $flush = true): void
    {
        $this->getEntityManager()->remove($account);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
