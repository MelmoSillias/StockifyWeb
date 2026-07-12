<?php

namespace App\Tenancy\Domain\Repository;

use App\Tenancy\Domain\Entity\Shop;
use Symfony\Component\Uid\Uuid;

interface ShopRepositoryInterface
{
    public function save(Shop $shop, bool $flush = true): void;

    public function findById(Uuid $id): ?Shop;

    public function countAll(): int;

    /** @return list<Shop> */
    public function findAllOrderedByName(): array;
}
