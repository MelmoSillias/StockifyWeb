<?php

namespace App\Shop\Application\Command\CreateShop;

use App\Shop\Domain\Entity\Shop;
use App\Shop\Domain\Repository\ShopRepositoryInterface;

final class CreateShopHandler
{
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository,
    ) {
    }

    public function handle(CreateShopCommand $command): Shop
    {
        $name = trim($command->name);
        $slug = strtolower(trim($command->slug));

        if ('' === $name || '' === $slug) {
            throw new \InvalidArgumentException('Le nom et le slug sont obligatoires.');
        }

        if (!preg_match('/^[a-z0-9-]{2,100}$/', $slug)) {
            throw new \InvalidArgumentException('Slug invalide.');
        }

        if (null !== $this->shopRepository->findBySlug($slug)) {
            throw new \InvalidArgumentException('Ce slug est déjà utilisé.');
        }

        $shop = new Shop($name, $slug);
        $shop->setCurrency($command->currency);
        $shop->setAddress($command->address);
        $shop->setPhone($command->phone);
        $this->shopRepository->save($shop);

        return $shop;
    }
}
