<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class SignupEssentialsFixture extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['essentials'];
    }

    public function load(ObjectManager $manager): void
    {
        SignupEssentialsSeeder::seed($manager);
    }
}
