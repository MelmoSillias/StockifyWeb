<?php

namespace App\DataFixtures;

use App\AccessAudit\Domain\Entity\Role;
use App\AccessAudit\Domain\Entity\UserRole;
use App\IdentityAccess\Domain\Entity\User;
use App\Shop\Domain\Entity\Shop;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixture extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function getDependencies(): array
    {
        return [RolePermissionFixture::class, ShopFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        $owner = new User(
            'owner@stockify.local',
            'owner',
            'placeholder',
            'Owner',
            'Demo',
        );
        $owner->setPasswordHash($this->passwordHasher->hashPassword($owner, 'Demo123!'));
        $owner->activate();
        $owner->promoteToPlatformOwner();
        $owner->syncSymfonyRoles(['admin']);

        $defaultShop = $this->getReference(FixtureReferences::DEFAULT_SHOP, Shop::class);

        $managerUser = new User(
            'manager@demo.local',
            'manager',
            'placeholder',
            'Manager',
            'Demo',
        );
        $managerUser->setPasswordHash($this->passwordHasher->hashPassword($managerUser, 'Demo123!'));
        $managerUser->activate();
        $managerUser->assignToShop($defaultShop->getId());
        $managerUser->syncSymfonyRoles(['gerant']);

        $manager->persist($owner);
        $manager->persist($managerUser);
        $manager->flush();

        $adminRole = $this->getReference('role.admin', Role::class);
        $gerantRole = $this->getReference('role.gerant', Role::class);

        $manager->persist(new UserRole($owner, $adminRole));
        $manager->persist(new UserRole($managerUser, $gerantRole));
        $manager->flush();

        $this->addReference(FixtureReferences::OWNER_USER, $owner);
        $this->addReference(FixtureReferences::MANAGER_USER, $managerUser);
    }
}
