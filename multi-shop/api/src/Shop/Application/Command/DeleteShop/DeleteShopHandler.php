<?php

namespace App\Shop\Application\Command\DeleteShop;

use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use App\Shop\Domain\Repository\ShopRepositoryInterface;

final class DeleteShopHandler
{
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function handle(DeleteShopCommand $command): void
    {
        $shop = $this->shopRepository->findById($command->shopId);
        if (null === $shop) {
            throw new \InvalidArgumentException('Boutique introuvable.');
        }

        $users = $this->userRepository->findByShopId($shop->getId());
        if ([] !== $users) {
            throw new \DomainException('Impossible de supprimer une boutique avec des utilisateurs.');
        }

        $shop->deactivate();
        $this->shopRepository->save($shop);
    }
}
