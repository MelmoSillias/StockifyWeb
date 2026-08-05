<?php

namespace App\Shop\Application\Command\RemoveShopMembership;

use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use App\Shop\Domain\Repository\ShopRepositoryInterface;

final class RemoveShopMembershipHandler
{
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function handle(RemoveShopMembershipCommand $command): User
    {
        $shop = $this->shopRepository->findById($command->shopId);
        if (null === $shop) {
            throw new \InvalidArgumentException('Boutique introuvable.');
        }

        $user = $this->userRepository->findById($command->userId);
        if (null === $user) {
            throw new \InvalidArgumentException('Utilisateur introuvable.');
        }

        if (!$user->belongsToShop($shop->getId())) {
            throw new \InvalidArgumentException('L\'utilisateur n\'est pas membre de cette boutique.');
        }

        if (1 === count($user->getShopIds())) {
            throw new \InvalidArgumentException('Impossible de retirer la dernière appartenance boutique.');
        }

        $user->removeShopMembership($shop->getId());
        $this->userRepository->save($user);

        return $user;
    }
}
