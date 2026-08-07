<?php

namespace App\Tests\Support;

use App\AccessAudit\Domain\Entity\UserRole;
use App\DataFixtures\SignupEssentialsSeeder;
use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccessAuditTestSeeder
{
    public static function seed(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): User
    {
        SignupEssentialsSeeder::seed($em);

        $adminRole = $em->getRepository(\App\AccessAudit\Domain\Entity\Role::class)->findOneBy(['code' => 'admin']);
        if (null === $adminRole) {
            throw new \RuntimeException('Admin role missing from signup essentials seed.');
        }

        $user = new User('admin-test@stockify.local', 'admin-test', '', 'Admin', 'Test');
        $user->setPasswordHash($passwordHasher->hashPassword($user, 'password123'));
        $user->activate();
        $em->persist($user);
        $em->persist(new UserRole($user, $adminRole));
        $em->flush();

        return $user;
    }
}
