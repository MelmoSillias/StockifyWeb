<?php

namespace App\DataFixtures;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Entity\ProductCategory;
use App\Catalog\Domain\Entity\ProductVariant;
use App\Catalog\Domain\Entity\UnitOfMeasure;
use App\Catalog\Domain\Enum\SaleMode;
use App\IdentityAccess\Domain\Entity\User;
use App\Inventory\Application\Service\StockMovementService;
use App\Inventory\Domain\Entity\StockPolicy;
use App\Inventory\Domain\Enum\StockPolicyStrategy;
use App\SharedKernel\Domain\ValueObject\TenantContext;
use App\Tenancy\Domain\Entity\Account;
use App\Tenancy\Domain\Entity\Shop;
use App\Tenancy\Domain\Enum\AccountMemberRole;
use App\Tenancy\Domain\Enum\ShopMemberRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class CatalogFixture extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly StockMovementService $stockMovementService,
    ) {
    }

    public function getDependencies(): array
    {
        return [TenancyFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        $units = $this->seedUnits($manager);

        /** @var Account $account */
        $account = $this->getReference(FixtureReferences::DEMO_ACCOUNT, Account::class);
        /** @var Shop $shop */
        $shop = $this->getReference(FixtureReferences::DEMO_SHOP_MAIN, Shop::class);
        /** @var User $owner */
        $owner = $this->getReference(FixtureReferences::DEMO_OWNER_USER, User::class);

        $beverages = new ProductCategory($account->getId(), $shop->getId(), 'Boissons');
        $grocery = new ProductCategory($account->getId(), $shop->getId(), 'Épicerie');

        $water = new Product($account->getId(), $shop->getId(), 'Eau minérale 1L');
        $water->setCategory($beverages);
        $water->setReference('EAU-001');
        $water->setDescription('Bouteille d\'eau minérale 1 litre');

        $rice = new Product($account->getId(), $shop->getId(), 'Riz parfumé');
        $rice->setCategory($grocery);
        $rice->setReference('RIZ-001');

        $waterVariant = new ProductVariant(
            $account->getId(),
            $shop->getId(),
            $water,
            'EAU-1L-UNIT',
            $units['piece'],
            SaleMode::Unit,
        );
        $waterVariant->setDefaultPrice('500.00');
        $waterVariant->setAlertThreshold('20');

        $riceVariant = new ProductVariant(
            $account->getId(),
            $shop->getId(),
            $rice,
            'RIZ-KG',
            $units['kg'],
            SaleMode::Weight,
        );
        $riceVariant->setDefaultPrice('750.00');
        $riceVariant->setAlertThreshold('10');

        $manager->persist($beverages);
        $manager->persist($grocery);
        $manager->persist($water);
        $manager->persist($rice);
        $manager->persist($waterVariant);
        $manager->persist($riceVariant);

        $waterPolicy = new StockPolicy($account->getId(), $shop->getId(), $waterVariant, StockPolicyStrategy::Fifo);
        $ricePolicy = new StockPolicy($account->getId(), $shop->getId(), $riceVariant, StockPolicyStrategy::Fifo);
        $manager->persist($waterPolicy);
        $manager->persist($ricePolicy);
        $manager->flush();

        $context = new TenantContext($owner, $account, $shop, AccountMemberRole::Owner, ShopMemberRole::Manager);

        $this->stockMovementService->receiveLot($context, $waterVariant, '100', '300.0000', 'LOT-EAU-001', 'Fournisseur Demo');
        $this->stockMovementService->receiveLot($context, $waterVariant, '50', '310.0000', 'LOT-EAU-002', 'Fournisseur Demo');
        $this->stockMovementService->receiveLot($context, $riceVariant, '80', '600.0000', 'LOT-RIZ-001', 'Fournisseur Demo');

        $this->addReference(FixtureReferences::UNIT_PIECE, $units['piece']);
        $this->addReference(FixtureReferences::UNIT_KG, $units['kg']);
        $this->addReference(FixtureReferences::CATEGORY_BEVERAGES, $beverages);
        $this->addReference(FixtureReferences::CATEGORY_GROCERY, $grocery);
        $this->addReference(FixtureReferences::PRODUCT_WATER, $water);
        $this->addReference(FixtureReferences::VARIANT_WATER_BOTTLE, $waterVariant);
    }

    /**
     * @return array{piece: UnitOfMeasure, kg: UnitOfMeasure}
     */
    private function seedUnits(ObjectManager $manager): array
    {
        $definitions = [
            'piece' => ['Pièce', 0],
            'kg' => ['Kilogramme', 3],
            'liter' => ['Litre', 3],
            'carton' => ['Carton', 0],
        ];

        $units = [];
        foreach ($definitions as $code => [$label, $decimals]) {
            $unit = $manager->getRepository(UnitOfMeasure::class)->findOneBy(['code' => $code]);
            if (!$unit instanceof UnitOfMeasure) {
                $unit = new UnitOfMeasure($code, $label, $decimals);
                $manager->persist($unit);
            }
            $units[$code] = $unit;
        }

        $manager->flush();

        return $units;
    }
}
