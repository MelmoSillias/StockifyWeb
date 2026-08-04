<?php

namespace App\DataFixtures;

use App\AccessAudit\Domain\Entity\Permission;
use App\AccessAudit\Domain\Entity\Role;
use App\AccessAudit\Domain\Entity\UserRole;
use App\AccessAudit\Domain\PermissionCatalog;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class RolePermissionFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $permissionsByCode = [];

        foreach (PermissionCatalog::all() as $def) {
            $permission = new Permission(
                $def['code'],
                $def['label'],
                $def['module'],
                $def['action'],
                $def['is_critical'],
            );
            $manager->persist($permission);
            $permissionsByCode[$def['code']] = $permission;
        }

        $rolesByCode = [];
        foreach (PermissionCatalog::predefinedRoles() as $def) {
            $role = new Role($def['code'], $def['label'], $def['description'], $def['is_system']);
            $manager->persist($role);
            $rolesByCode[$def['code']] = $role;
            $this->addReference('role.'.$def['code'], $role);
        }

        $manager->flush();

        foreach (PermissionCatalog::rolePermissions() as $roleCode => $permissionCodes) {
            $role = $rolesByCode[$roleCode];
            foreach ($permissionCodes as $code) {
                if (isset($permissionsByCode[$code])) {
                    $role->addPermission($permissionsByCode[$code]);
                }
            }
        }

        $manager->flush();
    }
}
