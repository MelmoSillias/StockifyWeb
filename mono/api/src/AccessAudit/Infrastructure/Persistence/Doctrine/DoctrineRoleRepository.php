<?php

namespace App\AccessAudit\Infrastructure\Persistence\Doctrine;

use App\AccessAudit\Domain\Entity\Role;
use App\AccessAudit\Domain\Repository\RoleRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Role>
 */
class DoctrineRoleRepository extends ServiceEntityRepository implements RoleRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    public function findByCode(string $code): ?Role
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function findById(Uuid $id): ?Role
    {
        return $this->find($id);
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isActive = true')
            ->orderBy('r.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Role $role, bool $flush = true): void
    {
        $this->getEntityManager()->persist($role);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Role $role, bool $flush = true): void
    {
        $this->getEntityManager()->remove($role);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
