<?php

namespace App\Tenancy\Infrastructure\Persistence\Doctrine;

use App\IdentityAccess\Domain\Entity\User;
use App\Tenancy\Domain\Entity\Account;
use App\Tenancy\Domain\Entity\AccountMember;
use App\Tenancy\Domain\Enum\AccountMemberStatus;
use App\Tenancy\Domain\Repository\AccountMemberRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccountMember>
 */
class DoctrineAccountMemberRepository extends ServiceEntityRepository implements AccountMemberRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountMember::class);
    }

    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('IDENTITY(m.user) = :userId')
            ->andWhere('m.status = :status')
            ->setParameter('userId', $user->getId(), 'uuid')
            ->setParameter('status', AccountMemberStatus::Active)
            ->getQuery()
            ->getResult();
    }

    public function findActiveMembership(Account $account, User $user): ?AccountMember
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.account = :accountId')
            ->andWhere('IDENTITY(m.user) = :userId')
            ->andWhere('m.status = :status')
            ->setParameter('accountId', $account->getId(), 'uuid')
            ->setParameter('userId', $user->getId(), 'uuid')
            ->setParameter('status', AccountMemberStatus::Active)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(AccountMember $member, bool $flush = true): void
    {
        $this->getEntityManager()->persist($member);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
