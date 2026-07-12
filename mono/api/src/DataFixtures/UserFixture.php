<?php

namespace App\DataFixtures;

use App\IdentityAccess\Domain\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixture extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
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
        $owner->setPasswordHash($this->passwordHasher->hashPassword($owner, '123'));
        $owner->activate();
        $owner->setRoles(['ROLE_USER']);

        $managerUser = new User(
            'manager@stockify.local',
            'manager',
            'placeholder',
            'Manager',
            'Demo',
        );
        $managerUser->setPasswordHash($this->passwordHasher->hashPassword($managerUser, '123'));
        $managerUser->activate();
        $managerUser->setRoles(['ROLE_USER']);

        $manager->persist($owner);
        $manager->persist($managerUser);
        $manager->flush();

        $this->addReference(FixtureReferences::OWNER_USER, $owner);
        $this->addReference(FixtureReferences::MANAGER_USER, $managerUser);
    }
}
