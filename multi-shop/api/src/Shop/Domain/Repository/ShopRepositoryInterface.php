<?php

namespace App\Shop\Domain\Repository;

use App\Shop\Domain\Entity\Shop;
use Symfony\Component\Uid\Uuid;

interface ShopRepositoryInterface
{
    public function findById(Uuid $id): ?Shop;

    public function findBySlug(string $slug): ?Shop;

    /** @return list<Shop> */
    public function findAllOrderedByName(): array;

    /** @return list<Shop> */
    public function findByTenantAccountId(Uuid $tenantAccountId): array;

    public function save(Shop $shop, bool $flush = true): void;

    public function remove(Shop $shop, bool $flush = true): void;
}
