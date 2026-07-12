<?php

namespace App\DataFixtures;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Entity\ProductCategory;
use App\Catalog\Domain\Entity\ProductVariant;
use App\Catalog\Domain\Entity\UnitOfMeasure;
use App\Catalog\Domain\Enum\SaleMode;
use App\Inventory\Application\Service\StockMovementService;
use App\Inventory\Domain\Entity\StockPolicy;
use App\Inventory\Domain\Enum\StockPolicyStrategy;
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
        return [UserFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        $units = $this->seedUnits($manager);

        $beverages = new ProductCategory('Boissons');
        $grocery = new ProductCategory('Épicerie');

        $water = new Product('Eau minérale 1L');
        $water->setCategory($beverages);
        $water->setReference('EAU-001');
        $water->setDescription('Bouteille d\'eau minérale 1 litre');

        $rice = new Product('Riz parfumé');
        $rice->setCategory($grocery);
        $rice->setReference('RIZ-001');

        $waterVariant = new ProductVariant(
            $water,
            'EAU-1L-UNIT',
            $units['piece'],
            SaleMode::Unit,
        );
        $waterVariant->setDefaultPrice('500.00');
        $waterVariant->setAlertThreshold('20');

        $riceVariant = new ProductVariant(
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

        $manager->persist(new StockPolicy($waterVariant, StockPolicyStrategy::Fifo));
        $manager->persist(new StockPolicy($riceVariant, StockPolicyStrategy::Fifo));
        $manager->flush();

        $this->stockMovementService->receiveLot($waterVariant, '100', '300.0000', 'LOT-EAU-001', 'Fournisseur Demo');
        $this->stockMovementService->receiveLot($waterVariant, '50', '310.0000', 'LOT-EAU-002', 'Fournisseur Demo');
        $this->stockMovementService->receiveLot($riceVariant, '80', '600.0000', 'LOT-RIZ-001', 'Fournisseur Demo');

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
