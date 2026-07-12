<?php

namespace App\Tenancy\Infrastructure\Persistence\Doctrine;

use App\Tenancy\Domain\Entity\Account;
use App\Tenancy\Domain\Repository\AccountRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Account>
 */
class DoctrineAccountRepository extends ServiceEntityRepository implements AccountRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    public function save(Account $account, bool $flush = true): void
    {
        $this->getEntityManager()->persist($account);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(Uuid $id): ?Account
    {
        return $this->find($id);
    }

    public function countAll(): int
    {
        return $this->count([]);
    }

    public function findAllOrderedByName(): array
    {
        return $this->findBy([], ['name' => 'ASC']);
    }
}
