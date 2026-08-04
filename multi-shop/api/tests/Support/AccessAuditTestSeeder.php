<?php

namespace App\Tests\Support;

use App\AccessAudit\Domain\Entity\Permission;
use App\AccessAudit\Domain\Entity\Role;
use App\AccessAudit\Domain\Entity\UserRole;
use App\AccessAudit\Domain\PermissionCatalog;
use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccessAuditTestSeeder
{
    public static function seed(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): User
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
            $em->persist($permission);
            $permissionsByCode[$def['code']] = $permission;
        }

        $rolesByCode = [];
        foreach (PermissionCatalog::predefinedRoles() as $def) {
            $role = new Role($def['code'], $def['label'], $def['description'], $def['is_system']);
            $em->persist($role);
            $rolesByCode[$def['code']] = $role;
        }

        $em->flush();

        foreach (PermissionCatalog::rolePermissions() as $roleCode => $permissionCodes) {
            $role = $rolesByCode[$roleCode];
            foreach ($permissionCodes as $code) {
                if (isset($permissionsByCode[$code])) {
                    $role->addPermission($permissionsByCode[$code]);
                }
            }
        }

        $adminRole = $rolesByCode['admin'];

        $user = new User('admin-test@stockify.local', 'admin-test', '', 'Admin', 'Test');
        $user->setPasswordHash($passwordHasher->hashPassword($user, 'password123'));
        $user->activate();
        $user->syncSymfonyRoles(['admin']);
        $em->persist($user);
        $em->persist(new UserRole($user, $adminRole));
        $em->flush();

        return $user;
    }
}
