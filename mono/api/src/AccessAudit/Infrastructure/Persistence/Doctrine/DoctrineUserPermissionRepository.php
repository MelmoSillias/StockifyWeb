<?php

namespace App\AccessAudit\Infrastructure\Persistence\Doctrine;

use App\AccessAudit\Domain\Entity\UserPermission;
use App\AccessAudit\Domain\Repository\UserPermissionRepositoryInterface;
use App\IdentityAccess\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserPermission>
 */
class DoctrineUserPermissionRepository extends ServiceEntityRepository implements UserPermissionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPermission::class);
    }

    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    public function save(UserPermission $userPermission, bool $flush = true): void
    {
        $this->getEntityManager()->persist($userPermission);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserPermission $userPermission, bool $flush = true): void
    {
        $this->getEntityManager()->remove($userPermission);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function removeAllForUser(User $user, bool $flush = true): void
    {
        $this->createQueryBuilder('up')
            ->delete()
            ->andWhere('up.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
