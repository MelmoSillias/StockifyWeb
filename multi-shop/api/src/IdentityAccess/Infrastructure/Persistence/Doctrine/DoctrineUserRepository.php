<?php

namespace App\IdentityAccess\Infrastructure\Persistence\Doctrine;

use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use App\IdentityAccess\Infrastructure\Security\AuthUserResolver;
use App\IdentityAccess\Infrastructure\Security\LoginContextHolder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<User>
 */
class DoctrineUserRepository extends ServiceEntityRepository implements UserRepositoryInterface, PasswordUpgraderInterface, UserLoaderInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly AuthUserResolver $authUserResolver,
        private readonly LoginContextHolder $loginContextHolder,
    ) {
        parent::__construct($registry, User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => strtolower($email)]);
    }

    public function findByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => strtolower($username)]);
    }

    public function findByUsernameAndShop(string $username, Uuid $shopId): ?User
    {
        return $this->findOneBy([
            'username' => strtolower($username),
            'shopId' => $shopId,
        ]);
    }

    public function findByShopId(Uuid $shopId): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.shopMemberships', 'm')
            ->andWhere('u.shopId = :shopId OR m.shopId = :shopId')
            ->setParameter('shopId', $shopId, 'uuid')
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    public function findPlatformOwner(): ?User
    {
        return $this->findOneBy(['isPlatformOwner' => true]);
    }

    public function findById(Uuid $id): ?User
    {
        return $this->find($id);
    }

    public function findByIdentityId(Uuid $identityId): ?User
    {
        return $this->findOneBy(['identityId' => $identityId]);
    }

    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->authUserResolver->resolve($identifier, $this->loginContextHolder->getShopSlug());
    }

    public function save(User $user, bool $flush = true): void
    {
        $this->getEntityManager()->persist($user);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPasswordHash($newHashedPassword);
        $this->save($user);
    }
}
