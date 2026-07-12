<?php

namespace App\DataFixtures;

/**
 * Clés de références partagées entre fixtures (ObjectManager::addReference / getReference).
 */
final class FixtureReferences
{
    public const SUPER_ADMIN_USER = 'user.super_admin';

    public const DEMO_OWNER_USER = 'user.demo_owner';

    public const DEMO_MANAGER_USER = 'user.demo_manager';

    public const DEMO_ACCOUNT = 'tenancy.demo_account';

    public const DEMO_SHOP_MAIN = 'tenancy.demo_shop_main';

    public const DEMO_SHOP_SECONDARY = 'tenancy.demo_shop_secondary';

    public const UNIT_PIECE = 'catalog.unit_piece';

    public const UNIT_KG = 'catalog.unit_kg';

    public const CATEGORY_BEVERAGES = 'catalog.category_beverages';

    public const CATEGORY_GROCERY = 'catalog.category_grocery';

    public const PRODUCT_WATER = 'catalog.product_water';

    public const VARIANT_WATER_BOTTLE = 'catalog.variant_water_bottle';
}
