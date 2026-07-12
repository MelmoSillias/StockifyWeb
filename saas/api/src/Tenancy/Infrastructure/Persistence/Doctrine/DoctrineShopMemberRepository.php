<?php

namespace App\Tenancy\Infrastructure\Persistence\Doctrine;

use App\IdentityAccess\Domain\Entity\User;
use App\Tenancy\Domain\Entity\Shop;
use App\Tenancy\Domain\Entity\ShopMember;
use App\Tenancy\Domain\Enum\ShopMemberStatus;
use App\Tenancy\Domain\Repository\ShopMemberRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShopMember>
 */
class DoctrineShopMemberRepository extends ServiceEntityRepository implements ShopMemberRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopMember::class);
    }

    public function findActiveMembership(Shop $shop, User $user): ?ShopMember
    {
        return $this->findOneBy([
            'shop' => $shop,
            'user' => $user,
            'status' => ShopMemberStatus::Active,
        ]);
    }

    public function save(ShopMember $member, bool $flush = true): void
    {
        $this->getEntityManager()->persist($member);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
