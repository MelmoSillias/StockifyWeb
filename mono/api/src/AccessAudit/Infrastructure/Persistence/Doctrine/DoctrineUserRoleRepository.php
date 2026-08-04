<?php

namespace App\AccessAudit\Infrastructure\Persistence\Doctrine;

use App\AccessAudit\Domain\Entity\UserRole;
use App\AccessAudit\Domain\Repository\UserRoleRepositoryInterface;
use App\IdentityAccess\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserRole>
 */
class DoctrineUserRoleRepository extends ServiceEntityRepository implements UserRoleRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRole::class);
    }

    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    public function save(UserRole $userRole, bool $flush = true): void
    {
        $this->getEntityManager()->persist($userRole);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserRole $userRole, bool $flush = true): void
    {
        $this->getEntityManager()->remove($userRole);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function removeAllForUser(User $user, bool $flush = true): void
    {
        $this->createQueryBuilder('ur')
            ->delete()
            ->andWhere('ur.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
