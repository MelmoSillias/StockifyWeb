<?php

namespace App\DataFixtures;

use App\AccessAudit\Domain\Entity\Role;
use App\AccessAudit\Domain\PermissionCatalog;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class RolePermissionFixture extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['demo'];
    }

    public function load(ObjectManager $manager): void
    {
        SignupEssentialsSeeder::seed($manager);

        foreach (PermissionCatalog::predefinedRoles() as $def) {
            $role = $manager->getRepository(Role::class)->findOneBy(['code' => $def['code']]);
            if (null !== $role) {
                $this->addReference('role.'.$def['code'], $role);
            }
        }
    }
}
