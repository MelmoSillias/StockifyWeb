<?php

namespace App\DataFixtures;

use App\AccessAudit\Domain\Entity\Permission;
use App\AccessAudit\Domain\Entity\Role;
use App\AccessAudit\Domain\PermissionCatalog;
use App\Catalog\Domain\Entity\UnitOfMeasure;
use Doctrine\Persistence\ObjectManager;

/**
 * RBAC catalogue + base units required before account signup.
 * Keep in sync with seeds/generate-signup-seed.php → seed-signup-essentials.sql.
 */
final class SignupEssentialsSeeder
{
    /** @var list<array{0: string, 1: string, 2: int}> */
    private const UNITS = [
        ['piece', 'Pièce', 0],
        ['kg', 'Kilogramme', 3],
        ['liter', 'Litre', 3],
        ['carton', 'Carton', 0],
    ];

    public static function seed(ObjectManager $manager): void
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

        foreach (self::UNITS as [$code, $label, $decimals]) {
            $existing = $manager->getRepository(UnitOfMeasure::class)->findOneBy(['code' => $code]);
            if (!$existing instanceof UnitOfMeasure) {
                $manager->persist(new UnitOfMeasure($code, $label, $decimals));
            }
        }

        $manager->flush();
    }
}
