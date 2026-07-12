<?php

namespace App\DataFixtures;

use App\IdentityAccess\Domain\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SuperAdminFixture extends Fixture
{
    public const EMAIL = 'admin@stockify.local';
    public const USERNAME = 'admin';

    public const PASSWORD = 'Admin123!';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $existing = $manager->getRepository(User::class)->findOneBy(['email' => self::EMAIL]);
        if ($existing instanceof User) {
            $this->addReference(FixtureReferences::SUPER_ADMIN_USER, $existing);

            return;
        }

        $user = new User(self::EMAIL, self::USERNAME, '', 'Super', 'Admin');
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, self::PASSWORD));
        $user->promoteSuperAdmin();

        $manager->persist($user);
        $manager->flush();

        $this->addReference(FixtureReferences::SUPER_ADMIN_USER, $user);
    }
}
