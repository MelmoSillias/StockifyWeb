<?php

namespace App\DataFixtures;

/**
 * Clés de références partagées entre fixtures.
 */
final class FixtureReferences
{
    public const OWNER_USER = 'user.owner';

    public const MANAGER_USER = 'user.manager';

    public const DEFAULT_SHOP = 'shop.default';

    public const UNIT_PIECE = 'catalog.unit_piece';

    public const UNIT_KG = 'catalog.unit_kg';

    public const CATEGORY_BEVERAGES = 'catalog.category_beverages';

    public const CATEGORY_GROCERY = 'catalog.category_grocery';

    public const PRODUCT_WATER = 'catalog.product_water';

    public const VARIANT_WATER_BOTTLE = 'catalog.variant_water_bottle';
}
