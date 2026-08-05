<?php

namespace App\Shop\Application\Command\AddShopMembership;

use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use App\Shop\Domain\Repository\ShopRepositoryInterface;

final class AddShopMembershipHandler
{
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function handle(AddShopMembershipCommand $command): User
    {
        $shop = $this->shopRepository->findById($command->shopId);
        if (null === $shop) {
            throw new \InvalidArgumentException('Boutique introuvable.');
        }

        $user = $this->userRepository->findById($command->userId);
        if (null === $user) {
            throw new \InvalidArgumentException('Utilisateur introuvable.');
        }

        if ($user->isPlatformOwner()) {
            throw new \InvalidArgumentException('Un platform owner ne peut pas être membre d\'une boutique.');
        }

        $shopTenantId = $shop->getTenantAccountId();
        $userTenantId = $user->getTenantAccountId();

        if (null !== $userTenantId && null !== $shopTenantId && !$userTenantId->equals($shopTenantId)) {
            throw new \InvalidArgumentException('La boutique n\'appartient pas au même tenant que l\'utilisateur.');
        }

        if (null !== $userTenantId && null === $shopTenantId) {
            throw new \InvalidArgumentException('Impossible d\'ajouter un utilisateur tenant à une boutique sans tenant.');
        }

        if (null === $userTenantId && null !== $shopTenantId) {
            $user->assignToTenantAccount($shopTenantId);
        }

        $makePrimary = [] === $user->getShopIds();
        $user->addShopMembership($shop->getId(), $makePrimary);
        $this->userRepository->save($user);

        return $user;
    }
}
