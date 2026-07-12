<?php

namespace App\DataFixtures;

use App\IdentityAccess\Domain\Entity\User;
use App\Tenancy\Domain\Entity\Account;
use App\Tenancy\Domain\Entity\AccountMember;
use App\Tenancy\Domain\Entity\Shop;
use App\Tenancy\Domain\Entity\ShopMember;
use App\Tenancy\Domain\Enum\AccountMemberRole;
use App\Tenancy\Domain\Enum\AccountStatus;
use App\Tenancy\Domain\Enum\ShopMemberRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class TenancyFixture extends Fixture implements DependentFixtureInterface
{
    public const OWNER_EMAIL = 'owner@demo.stockify.local';
    public const OWNER_USERNAME = 'owner';

    public const MANAGER_EMAIL = 'manager@demo.stockify.local';
    public const MANAGER_USERNAME = 'manager';

    public const PASSWORD = 'Demo123!';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function getDependencies(): array
    {
        return [SuperAdminFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        $owner = $this->createUser($manager, self::OWNER_EMAIL, self::OWNER_USERNAME, 'Demo', 'Owner');
        $managerUser = $this->createUser($manager, self::MANAGER_EMAIL, self::MANAGER_USERNAME, 'Shop', 'Manager');

        $account = new Account('Demo Commerce', 'demo-commerce', 'XOF', 'Africa/Abidjan');
        $accountMember = new AccountMember($account, $owner, AccountMemberRole::Owner);
        $managerMember = new AccountMember($account, $managerUser, AccountMemberRole::Member);

        $mainShop = new Shop($account, 'Boutique Principale', 'boutique-principale');
        $secondaryShop = new Shop($account, 'Boutique Secondaire', 'boutique-secondaire');

        $mainShopMember = new ShopMember($mainShop, $managerUser, ShopMemberRole::Manager, $managerMember);
        $secondaryShopMember = new ShopMember($secondaryShop, $managerUser, ShopMemberRole::Viewer, $managerMember);

        $manager->persist($account);
        $manager->persist($accountMember);
        $manager->persist($managerMember);
        $manager->persist($mainShop);
        $manager->persist($secondaryShop);
        $manager->persist($mainShopMember);
        $manager->persist($secondaryShopMember);
        $manager->flush();

        $this->addReference(FixtureReferences::DEMO_OWNER_USER, $owner);
        $this->addReference(FixtureReferences::DEMO_MANAGER_USER, $managerUser);
        $this->addReference(FixtureReferences::DEMO_ACCOUNT, $account);
        $this->addReference(FixtureReferences::DEMO_SHOP_MAIN, $mainShop);
        $this->addReference(FixtureReferences::DEMO_SHOP_SECONDARY, $secondaryShop);
    }

    private function createUser(ObjectManager $manager, string $email, string $username, string $firstName, string $lastName): User
    {
        $existing = $manager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing instanceof User) {
            return $existing;
        }

        $user = new User($email, $username, '', $firstName, $lastName);
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, self::PASSWORD));
        $user->activate();
        $manager->persist($user);

        return $user;
    }
}
