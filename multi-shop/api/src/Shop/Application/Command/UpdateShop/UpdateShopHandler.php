<?php

namespace App\Shop\Application\Command\UpdateShop;

use App\Shop\Domain\Entity\Shop;
use App\Shop\Domain\Enum\ShopStatus;
use App\Shop\Domain\Repository\ShopRepositoryInterface;

final class UpdateShopHandler
{
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository,
    ) {
    }

    public function handle(UpdateShopCommand $command): Shop
    {
        $shop = $this->shopRepository->findById($command->shopId);
        if (null === $shop) {
            throw new \InvalidArgumentException('Boutique introuvable.');
        }

        if (null !== $command->name && '' !== trim($command->name)) {
            $shop->setName($command->name);
        }

        if (null !== $command->slug && '' !== trim($command->slug)) {
            $slug = strtolower(trim($command->slug));
            $existing = $this->shopRepository->findBySlug($slug);
            if (null !== $existing && !$existing->getId()->equals($shop->getId())) {
                throw new \InvalidArgumentException('Ce slug est déjà utilisé.');
            }
            $shop->setSlug($slug);
        }

        if (null !== $command->currency) {
            $shop->setCurrency($command->currency);
        }

        if (null !== $command->address) {
            $shop->setAddress($command->address);
        }

        if (null !== $command->phone) {
            $shop->setPhone($command->phone);
        }

        if (null !== $command->status) {
            match ($command->status) {
                ShopStatus::Active->value => $shop->activate(),
                ShopStatus::Inactive->value => $shop->deactivate(),
                default => throw new \InvalidArgumentException('Statut invalide.'),
            };
        }

        $this->shopRepository->save($shop);

        return $shop;
    }
}
