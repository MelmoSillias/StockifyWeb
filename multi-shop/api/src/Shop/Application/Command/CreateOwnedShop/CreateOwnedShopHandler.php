<?php

namespace App\Shop\Application\Command\CreateOwnedShop;

use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use App\Integration\Application\Service\TenantFeatureGuard;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use App\Shop\Application\Command\CreateShop\CreateShopCommand;
use App\Shop\Application\Command\CreateShop\CreateShopHandler;
use App\Shop\Domain\Entity\Shop;
use App\Shop\Domain\Repository\ShopRepositoryInterface;

final class CreateOwnedShopHandler
{
    public function __construct(
        private readonly CreateShopHandler $createShopHandler,
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
        private readonly TenantFeatureGuard $tenantFeatureGuard,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function handle(User $actor, CreateShopCommand $command): Shop
    {
        if ($actor->isPlatformOwner()) {
            return $this->createShopHandler->handle($command);
        }

        $tenantAccountId = $actor->getTenantAccountId();
        if (null === $tenantAccountId) {
            throw new \DomainException('Aucun compte tenant associé à cet utilisateur.');
        }

        $account = $this->tenantAccountRepository->findById($tenantAccountId);
        if (null === $account) {
            throw new \InvalidArgumentException('Compte tenant introuvable.');
        }

        if ($account->isSuspended()) {
            throw new \DomainException('Le compte tenant est suspendu.');
        }

        $this->tenantFeatureGuard->assertCanCreateShop($account);

        $shop = $this->createShopHandler->handle($command);
        $shop->setTenantAccountId($account->getId());
        $this->shopRepository->save($shop);

        $makePrimary = [] === $actor->getShopIds();
        $actor->addShopMembership($shop->getId(), $makePrimary);
        $this->userRepository->save($actor);

        return $shop;
    }
}
